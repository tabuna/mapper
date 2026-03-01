<?php

namespace Tabuna\Map;

use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;
use Tabuna\Map\Container\ContainerResolver;
use Tabuna\Map\Container\PsrContainerAdapter;
use Tabuna\Map\Source\Contracts\ObjectPayloadExtractor;
use Tabuna\Map\Source\SourceNormalizer;
use Tabuna\Map\Target\TargetFactory;
use Tabuna\Map\Target\TargetHydrator;
use Tabuna\Map\Transform\AttributeRules;
use UnexpectedValueException;

class Mapper
{
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

    protected SourceNormalizer $sourceNormalizer;

    protected AttributeRules $attributeRules;

    protected TargetFactory $targetFactory;

    protected TargetHydrator $targetHydrator;

    /**
     * @var array<int, callable|class-string>
     */
    protected array $mappers = [];

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
        $this->container = ContainerResolver::resolve($container);
        $this->sourceNormalizer = new SourceNormalizer();
        $this->attributeRules = new AttributeRules();
        $this->targetFactory = new TargetFactory($this->container);
        $this->targetHydrator = new TargetHydrator();

        $this->source = $this->sourceNormalizer->prepareSource($source);
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
        ContainerResolver::useContainer($container);
    }

    /**
     * Configure global PSR-11 container for all future map() calls.
     */
    public static function usePsrContainer(ContainerInterface $container): void
    {
        ContainerResolver::usePsrContainer($container);
    }

    /**
     * Reset global container configuration.
     */
    public static function resetContainer(): void
    {
        ContainerResolver::reset();
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
     * Register custom source extractor for object payload normalization.
     *
     * @param ObjectPayloadExtractor|class-string $extractor
     *
     * @return $this
     */
    public function withSourceExtractor(ObjectPayloadExtractor|string $extractor, bool $prepend = true): self
    {
        $resolved = is_string($extractor)
            ? $this->container->make($extractor)
            : $extractor;

        if (! $resolved instanceof ObjectPayloadExtractor) {
            throw new LogicException(
                'Source extractor must implement '.ObjectPayloadExtractor::class.'.'
            );
        }

        if ($prepend) {
            $this->sourceNormalizer->prependObjectExtractor($resolved);
        } else {
            $this->sourceNormalizer->appendObjectExtractor($resolved);
        }

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
        $this->attributeRules->rename($map);

        return $this;
    }

    /**
     * Convert snake_case and kebab-case keys to camelCase before mapping.
     *
     * @return $this
     */
    public function snakeToCamelKeys(): self
    {
        $this->attributeRules->snakeToCamelKeys();

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
        $this->attributeRules->only($keys);

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
        $this->attributeRules->except($keys);

        return $this;
    }

    /**
     * Select nested payload path before mapping (dot notation).
     *
     * @return $this
     */
    public function path(string $path): self
    {
        $this->attributeRules->path($path);

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
        return $this->targetHydrator->fill($target, $attributes);
    }

    /**
     * Create target object and resolve constructor arguments from attributes/container.
     *
     * @param class-string $targetClass
     * @param array        $attributes
     */
    protected function makeTarget(string $targetClass, array $attributes): object
    {
        return $this->targetFactory->make($targetClass, $attributes);
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
        return $this->sourceNormalizer->normalizeAttributes($item);
    }

    /**
     * Normalize an object source using common request-like extractors.
     */
    protected function normalizeObjectAttributes(object $item): array
    {
        return $this->sourceNormalizer->normalizeObjectAttributes($item);
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
        return $this->applyAttributeRules($this->normalizeAttributes($item));
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
        return $this->attributeRules->extractPathAttributes($attributes, $path);
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
        return $this->attributeRules->transform($attributes);
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
        return $this->attributeRules->convertArrayKeysToCamelCase($attributes);
    }

    /**
     * Check if array keys are a zero-based sequential index.
     *
     * @param array $attributes
     */
    protected function isListArray(array $attributes): bool
    {
        return $this->attributeRules->isListArray($attributes);
    }

    /**
     * Convert a key from snake_case / kebab-case to camelCase.
     */
    protected function toCamelCase(string $key): string
    {
        return $this->attributeRules->toCamelCase($key);
    }

    /**
     * Determine if a property can be assigned on the target object.
     */
    protected function canAssignProperty(object $target, string $key): bool
    {
        return $this->targetHydrator->canAssignProperty($target, $key);
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
        $this->targetHydrator->assertNoUnknownAttributes($targetClass, $target, $attributes);
    }

    /**
     * Check if target is an Eloquent model without hard requiring Illuminate database package.
     */
    protected function isEloquentModel(object $target): bool
    {
        return $this->targetHydrator->isEloquentModel($target);
    }
}
