<?php

namespace Tabuna\Map;

use function array_diff;
use function array_keys;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use Tabuna\Map\Support\PsrContainerAdapter;
use Tabuna\Map\Support\SymfonyContainerAdapter;
use Throwable;
use UnexpectedValueException;

class Mapper
{
    /**
     * Optional Eloquent base model class used when Illuminate database package is installed.
     */
    protected const ELOQUENT_MODEL_CLASS = 'Illuminate\\Database\\Eloquent\\Model';

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
     * Source-to-target attribute name map.
     *
     * @var array<string, string>
     */
    protected array $attributeRenames = [];

    /**
     * Enable automatic snake_case/kebab-case to camelCase key conversion.
     */
    protected bool $snakeToCamel = false;

    /**
     * Keep only selected source keys.
     *
     * @var array<int, string>|null
     */
    protected ?array $onlyKeys = null;

    /**
     * Exclude selected source keys.
     *
     * @var array<int, string>
     */
    protected array $exceptKeys = [];

    /**
     * Optional dot-notated path to nested source payload.
     */
    protected ?string $sourcePath = null;

    /**
     * Fail mapping when payload contains unknown attributes.
     */
    protected bool $strict = false;

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
     * Rename source attributes before mapping.
     *
     * @param array<string, string> $map
     *
     * @return $this
     */
    public function rename(array $map): self
    {
        foreach ($map as $from => $to) {
            $this->attributeRenames[$from] = $to;
        }

        return $this;
    }

    /**
     * Convert snake_case and kebab-case keys to camelCase before mapping.
     *
     * @return $this
     */
    public function snakeToCamelKeys(): self
    {
        $this->snakeToCamel = true;

        return $this;
    }

    /**
     * Enable strict mode and fail on unknown attributes.
     *
     * @return $this
     */
    public function strict(bool $enabled = true): self
    {
        $this->strict = $enabled;

        return $this;
    }

    /**
     * Keep only selected source keys before mapping.
     *
     * @param array<int, string> $keys
     *
     * @return $this
     */
    public function only(array $keys): self
    {
        $this->onlyKeys = $keys;

        return $this;
    }

    /**
     * Exclude selected source keys before mapping.
     *
     * @param array<int, string> $keys
     *
     * @return $this
     */
    public function except(array $keys): self
    {
        $this->exceptKeys = [...$this->exceptKeys, ...$keys];

        return $this;
    }

    /**
     * Select nested payload path before mapping (dot notation).
     *
     * @return $this
     */
    public function path(string $path): self
    {
        $this->sourcePath = trim($path);

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
        $attributes = $this->normalizeForMapping($item);
        $target = $this->makeTarget($targetClass, $attributes);

        if ($this->strict && $this->mappers === []) {
            $this->assertNoUnknownAttributes($targetClass, $target, $attributes);
        }

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

        return $this->fill($target, $attributes);
    }

    /**
     * Fill the target object with data from the item.
     *
     * @param object $target
     * @param array  $attributes
     *
     * @return object
     */
    protected function fill(object $target, array $attributes): object
    {
        if ($this->isEloquentModel($target)) {
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
     * Create target object and resolve constructor arguments from attributes/container.
     *
     * @param class-string $targetClass
     * @param array        $attributes
     */
    protected function makeTarget(string $targetClass, array $attributes): object
    {
        try {
            $reflection = new ReflectionClass($targetClass);
            $constructor = $reflection->getConstructor();

            if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
                return $this->container->make($targetClass);
            }

            $arguments = [];
            $usesSourceAttributes = false;

            foreach ($constructor->getParameters() as $parameter) {
                $name = $parameter->getName();

                if (array_key_exists($name, $attributes)) {
                    $arguments[] = $attributes[$name];
                    $usesSourceAttributes = true;

                    continue;
                }

                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                    $arguments[] = $this->container->make($type->getName());

                    continue;
                }

                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();

                    continue;
                }

                if ($parameter->allowsNull()) {
                    $arguments[] = null;

                    continue;
                }

                return $this->container->make($targetClass);
            }

            if (! $usesSourceAttributes) {
                return $this->container->make($targetClass);
            }

            return $reflection->newInstanceArgs($arguments);
        } catch (ReflectionException) {
            return $this->container->make($targetClass);
        }
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
                ->map(fn ($item) => $this->normalizeForMapping($item))
                ->toArray();
        }

        return $this->normalizeForMapping($this->source);
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
            is_object($item)           => $this->normalizeObjectAttributes($item),
            default                    => (array) $item,
        };
    }

    /**
     * Normalize an object source using common request-like extractors.
     */
    protected function normalizeObjectAttributes(object $item): array
    {
        $extractors = [
            'all',
            'toArray',
            'get_params',
            'getParsedBody',
        ];

        foreach ($extractors as $method) {
            $attributes = $this->extractAttributesFromMethod($item, $method);

            if (is_array($attributes)) {
                return $attributes;
            }
        }

        return get_object_vars($item);
    }

    /**
     * Try extracting array payload via a parameterless method.
     */
    protected function extractAttributesFromMethod(object $item, string $method): ?array
    {
        if (! method_exists($item, $method)) {
            return null;
        }

        try {
            $resolved = $item->$method();
        } catch (Throwable) {
            return null;
        }

        return is_array($resolved) ? $resolved : null;
    }

    /**
     * Normalize and transform source item according to mapper configuration.
     *
     * @param mixed $item
     *
     * @return array
     */
    protected function normalizeForMapping(mixed $item): array
    {
        $attributes = $this->normalizeAttributes($item);

        if ($this->sourcePath !== null && $this->sourcePath !== '') {
            $attributes = $this->extractPathAttributes($attributes, $this->sourcePath);
        }

        return $this->applyAttributeRules($attributes);
    }

    /**
     * Resolve dot-notated path from normalized attributes.
     *
     * @param array  $attributes
     * @param string $path
     *
     * @return array
     */
    protected function extractPathAttributes(array $attributes, string $path): array
    {
        $resolved = $attributes;

        foreach (explode('.', $path) as $segment) {
            if ($segment === '') {
                continue;
            }

            if (! is_array($resolved) || ! array_key_exists($segment, $resolved)) {
                return [];
            }

            $resolved = $resolved[$segment];
        }

        return is_array($resolved) ? $resolved : [];
    }

    /**
     * Apply attribute rename and key-conversion rules.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function applyAttributeRules(array $attributes): array
    {
        if ($this->onlyKeys !== null) {
            $attributes = array_intersect_key($attributes, array_flip($this->onlyKeys));
        }

        if ($this->exceptKeys !== []) {
            foreach ($this->exceptKeys as $key) {
                unset($attributes[$key]);
            }
        }

        if ($this->attributeRenames !== []) {
            $renamed = [];

            foreach ($attributes as $key => $value) {
                $resolvedKey = is_string($key) && array_key_exists($key, $this->attributeRenames)
                    ? $this->attributeRenames[$key]
                    : $key;

                $renamed[$resolvedKey] = $value;
            }

            $attributes = $renamed;
        }

        if ($this->snakeToCamel) {
            $attributes = $this->convertArrayKeysToCamelCase($attributes);
        }

        return $attributes;
    }

    /**
     * Convert all associative keys to camelCase recursively.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function convertArrayKeysToCamelCase(array $attributes): array
    {
        if ($this->isListArray($attributes)) {
            return array_map(
                fn ($value) => is_array($value) ? $this->convertArrayKeysToCamelCase($value) : $value,
                $attributes
            );
        }

        $converted = [];

        foreach ($attributes as $key => $value) {
            $resolvedKey = is_string($key) ? $this->toCamelCase($key) : $key;

            $converted[$resolvedKey] = is_array($value)
                ? $this->convertArrayKeysToCamelCase($value)
                : $value;
        }

        return $converted;
    }

    /**
     * Check if array keys are a zero-based sequential index.
     *
     * @param array $attributes
     */
    protected function isListArray(array $attributes): bool
    {
        $index = 0;

        foreach (array_keys($attributes) as $key) {
            if ($key !== $index) {
                return false;
            }

            $index++;
        }

        return true;
    }

    /**
     * Convert a key from snake_case / kebab-case to camelCase.
     */
    protected function toCamelCase(string $key): string
    {
        $key = str_replace('-', '_', $key);
        $segments = explode('_', $key);
        $first = array_shift($segments);

        if ($first === null) {
            return $key;
        }

        return $first.implode('', array_map('ucfirst', $segments));
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
     * Ensure strict mode has no unknown attributes for the target shape.
     *
     * @param class-string $targetClass
     * @param array        $attributes
     */
    protected function assertNoUnknownAttributes(string $targetClass, object $target, array $attributes): void
    {
        $keys = array_values(array_filter(array_keys($attributes), 'is_string'));

        if ($keys === []) {
            return;
        }

        if ($this->isEloquentModel($target)) {
            $fillable = $target->getFillable();

            if ($fillable === []) {
                return;
            }

            $unknown = array_values(array_diff($keys, $fillable));

            if ($unknown === []) {
                return;
            }

            throw new InvalidArgumentException(
                sprintf(
                    'Unknown attributes for [%s]: %s',
                    $targetClass,
                    implode(', ', $unknown)
                )
            );
        }

        $allowed = [];
        $reflection = new ReflectionClass($targetClass);

        foreach ($reflection->getProperties() as $property) {
            if ($this->canAssignProperty($target, $property->getName())) {
                $allowed[] = $property->getName();
            }
        }

        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $parameter) {
                $allowed[] = $parameter->getName();
            }
        }

        $unknown = array_values(array_diff($keys, array_unique($allowed)));

        if ($unknown === []) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unknown attributes for [%s]: %s',
                $targetClass,
                implode(', ', $unknown)
            )
        );
    }

    /**
     * Check if target is an Eloquent model without hard requiring Illuminate database package.
     */
    protected function isEloquentModel(object $target): bool
    {
        $modelClass = self::ELOQUENT_MODEL_CLASS;

        return class_exists($modelClass)
            && $target instanceof $modelClass;
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
