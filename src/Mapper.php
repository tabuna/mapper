<?php

namespace Tabuna\Map;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Tabuna\Map\Support\PsrContainerAdapter;
use Tabuna\Map\Support\SymfonyContainerAdapter;
use Throwable;
use UnexpectedValueException;

class Mapper
{
    /**
     * Global configured container used by default for all mapper instances.
     */
    protected static ?ContainerContract $globalContainer = null;

    /**
     * Auto-detected runtime container cached for subsequent mapper instances.
     */
    protected static ?ContainerContract $autoDetectedContainer = null;

    /**
     * The source data to be mapped. Can be an object, array, or collection.
     */
    protected mixed $source;

    /**
     * Indicates whether the source should be treated as a collection of items.
     */
    protected bool $isCollection = false;

    /**
     * The IoC container used to resolve mappers and target classes.
     */
    protected ContainerContract $container;

    /**
     * @var array<int, callable|class-string>
     */
    protected array $mappers = [];

    /**
     * @param mixed                  $source    The data source to map from.
     * @param ContainerContract|null $container Optional dependency container.
     */
    public function __construct(mixed $source, ?ContainerContract $container = null)
    {
        $this->container = $container
            ?? self::$globalContainer
            ?? self::resolveAutoDetectedContainer()
            ?? Container::getInstance();

        $this->source = match (true) {
            $source instanceof Arrayable => $source->toArray(),
            is_string($source)           => json_decode($source, true, 512, JSON_THROW_ON_ERROR),
            default                      => $source,
        };
    }

    /**
     * Create a new Mapper instance.
     *
     * @param mixed $source
     *
     * @return static
     */
    public static function map(mixed $source): self
    {
        return new self($source);
    }

    /**
     * One-shot mapping helper: map source into target class.
     *
     * @param class-string $targetClass
     *
     * @return mixed
     */
    public static function into(mixed $source, string $targetClass): mixed
    {
        return self::map($source)->to($targetClass);
    }

    /**
     * One-shot collection mapping helper: map source into target class collection.
     *
     * @param class-string $targetClass
     */
    public static function intoMany(mixed $source, string $targetClass): Collection
    {
        return self::map($source)->toMany($targetClass);
    }

    /**
     * Create a new Mapper instance with an explicit container.
     */
    public static function mapUsingContainer(mixed $source, ContainerContract $container): self
    {
        return new self($source, $container);
    }

    /**
     * Create a new Mapper instance with an explicit PSR-11 container.
     */
    public static function mapUsingPsrContainer(mixed $source, ContainerInterface $container): self
    {
        return new self($source, new PsrContainerAdapter($container));
    }

    /**
     * Configure global Illuminate container for all future map() calls.
     */
    public static function useContainer(ContainerContract $container): void
    {
        self::$globalContainer = $container;
    }

    /**
     * Configure global PSR-11 container for all future map() calls.
     */
    public static function usePsrContainer(ContainerInterface $container): void
    {
        self::$globalContainer = new PsrContainerAdapter($container);
    }

    /**
     * Reset global container configuration.
     */
    public static function resetContainer(): void
    {
        self::$globalContainer = null;
        self::$autoDetectedContainer = null;
    }

    /**
     * Enable collection mode (map each item in iterable).
     *
     * @return $this
     */
    public function collection(): self
    {
        $this->isCollection = true;

        return $this;
    }

    /**
     * Register a custom mapper callback or invokable class name.
     *
     * @param callable|class-string $mapper
     *
     * @return $this
     */
    public function with(callable|string $mapper): self
    {
        $this->mappers[] = $mapper;

        return $this;
    }

    /**
     * Perform mapping to target class or object.
     *
     * @param class-string $targetClass
     *
     * @return mixed|Collection
     */
    public function to(string $targetClass)
    {
        if ($this->isCollection) {
            $this->assertCollectionSourceIsIterable();

            return Collection::make($this->source)
                ->map(fn ($item) => $this->mapItem($item, $targetClass));
        }

        return $this->mapItem($this->source, $targetClass);
    }

    /**
     * Map source as collection to target class.
     *
     * @param class-string $targetClass
     */
    public function toMany(string $targetClass): Collection
    {
        return $this->collection()->to($targetClass);
    }

    /**
     * Map a single item to the target class.
     *
     * @param mixed        $item
     * @param class-string $targetClass
     *
     * @return mixed
     */
    protected function mapItem(mixed $item, string $targetClass): mixed
    {
        $target = $this->container->make($targetClass);

        foreach ($this->mappers as $mapper) {
            $resolver = is_string($mapper)
                ? $this->container->make($mapper)
                : $mapper;

            if (! is_callable($resolver)) {
                throw new LogicException('Each mapper must be a callable or an invokable class name.');
            }

            $result = $resolver($item, $target);

            if (! is_object($result)) {
                throw new UnexpectedValueException('Custom mapper must return an object.');
            }

            return $result;
        }

        return $this->fill($target, $item);
    }

    /**
     * Fill the target object with data from the item.
     *
     * @param object $target
     * @param mixed  $item
     *
     * @return object
     */
    protected function fill(object $target, mixed $item): object
    {
        $attributes = $this->normalizeAttributes($item);

        if ($target instanceof Model) {
            return $target->fill($attributes);
        }

        foreach ($attributes as $key => $value) {
            if ($this->canAssignProperty($target, $key)) {
                $target->$key = $value;
            }
        }

        return $target;
    }

    /**
     * Get the mapped result as a plain array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if ($this->isCollection) {
            $this->assertCollectionSourceIsIterable();

            return Collection::make($this->source)
                ->map(fn ($item) => $this->normalizeAttributes($item))
                ->toArray();
        }

        return $this->normalizeAttributes($this->source);
    }

    /**
     * Get the mapped result as a JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Normalize any supported source item to an array of attributes.
     *
     * @param mixed $item
     *
     * @return array
     */
    protected function normalizeAttributes(mixed $item): array
    {
        return match (true) {
            is_array($item)            => $item,
            $item instanceof Arrayable => $item->toArray(),
            is_object($item)           => get_object_vars($item),
            default                    => (array) $item,
        };
    }

    /**
     * Determine if a property can be assigned on the target object.
     */
    protected function canAssignProperty(object $target, string $key): bool
    {
        if (! property_exists($target, $key)) {
            return false;
        }

        $property = new ReflectionProperty($target, $key);

        return $property->isPublic()
            && ! $property->isStatic()
            && ! $property->isReadOnly();
    }

    /**
     * Ensure collection mode receives iterable source.
     */
    protected function assertCollectionSourceIsIterable(): void
    {
        if (! is_iterable($this->source)) {
            throw new InvalidArgumentException('Collection mode expects iterable source data.');
        }
    }

    /**
     * Resolve and cache a runtime container from supported framework environments.
     */
    protected static function resolveAutoDetectedContainer(): ?ContainerContract
    {
        if (self::$autoDetectedContainer instanceof ContainerContract) {
            return self::$autoDetectedContainer;
        }

        $candidates = [
            self::detectGlobalContainerVariable(),
            self::detectSymfonyKernelContainer(),
            self::detectLaravelContainer(),
        ];

        foreach ($candidates as $candidate) {
            $resolved = self::normalizeContainerCandidate($candidate);

            if ($resolved instanceof ContainerContract) {
                self::$autoDetectedContainer = $resolved;

                return self::$autoDetectedContainer;
            }
        }

        return null;
    }

    /**
     * Normalize mixed container candidate into Illuminate container.
     */
    protected static function normalizeContainerCandidate(mixed $candidate): ?ContainerContract
    {
        if ($candidate instanceof ContainerContract) {
            return $candidate;
        }

        if ($candidate instanceof ContainerInterface) {
            return new PsrContainerAdapter($candidate);
        }

        if (is_object($candidate) && method_exists($candidate, 'get') && method_exists($candidate, 'has')) {
            return new PsrContainerAdapter(new SymfonyContainerAdapter($candidate));
        }

        return null;
    }

    /**
     * Detect Laravel container from helper runtime.
     */
    protected static function detectLaravelContainer(): mixed
    {
        if (! function_exists('app')) {
            return null;
        }

        try {
            return app();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Detect Symfony container exposed by global kernel instance.
     */
    protected static function detectSymfonyKernelContainer(): mixed
    {
        $kernel = $GLOBALS['kernel'] ?? null;

        if (! is_object($kernel) || ! method_exists($kernel, 'getContainer')) {
            return null;
        }

        try {
            return $kernel->getContainer();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Detect generic global container variable (for simple bootstrap setups).
     */
    protected static function detectGlobalContainerVariable(): mixed
    {
        return $GLOBALS['container'] ?? null;
    }
}
