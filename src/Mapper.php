<?php

namespace Tabuna\Map;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionProperty;

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
    protected Container $container;

    /**
     * @param mixed          $source    The data source to map from.
     * @param Container|null $container Optional dependency container.
     */
    public function __construct(mixed $source, ?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();

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
     * Perform mapping to target class or object.
     *
     * @param class-string $targetClass
     *
     * @return mixed|Collection
     */
    public function to(string $targetClass)
    {
        if ($this->isCollection) {
            return collect($this->source)
                ->map(fn ($item) => $this->mapItem($item, $targetClass));
        }

        return $this->mapItem($this->source, $targetClass);
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
        /*
        foreach ($this->mappers as $mapper) {
            if (is_string($mapper)) {
                $mapper = $this->container->make($mapper);
            }

            if (is_callable($mapper)) {
                return $mapper($this, $item);
            }

            throw new LogicException('Each mapper must be a class name or a callable.');
        }
        */

        $target = $this->container->make($targetClass);

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
            return collect($this->source)
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
}
