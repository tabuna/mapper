<?php

namespace Tabuna\Map\Transform;

use Closure;
use Illuminate\Contracts\Container\Container as ContainerContract;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Tabuna\Map\Attribute\Map;
use Tabuna\Map\Contracts\ConditionCallableInterface;
use Tabuna\Map\Contracts\TransformCallableInterface;

class MapAttributeProcessor
{
    public function __construct(protected ContainerContract $container) {}

    /**
     * Resolve target class from source class-level #[Map] metadata.
     *
     * @return class-string|null
     */
    public function resolveTargetClass(object $source): ?string
    {
        $matches = [];

        foreach ($this->getClassMapAttributes($source) as $map) {
            if (! is_string($map->target) || $map->target === '') {
                continue;
            }

            if ($this->evaluateCondition($map->if, null, $source, null)) {
                $matches[] = $map->target;
            }
        }

        if ($matches === []) {
            return null;
        }

        $unique = array_values(array_unique($matches));

        if (count($unique) > 1) {
            throw new LogicException(
                sprintf('Ambiguous class-level mapping for [%s]: %s', $source::class, implode(', ', $unique))
            );
        }

        return $unique[0];
    }

    /**
     * Apply class-level transform configured for concrete target class.
     */
    public function applyClassTransform(object $source, object $target, string $targetClass): object
    {
        $matches = [];

        foreach ($this->getClassMapAttributes($source) as $map) {
            if (! is_string($map->target) || $map->target !== $targetClass) {
                continue;
            }

            if ($this->evaluateCondition($map->if, null, $source, $target)) {
                $matches[] = $map;
            }
        }

        if ($matches === []) {
            return $target;
        }

        if (count($matches) > 1) {
            throw new LogicException(
                sprintf('Ambiguous class-level transform mapping for [%s] -> [%s].', $source::class, $targetClass)
            );
        }

        $map = $matches[0];

        if ($map->transform === null) {
            return $target;
        }

        $resolved = $this->applyTransform($map->transform, $target, $source, $target);

        if (! is_object($resolved) || ! $resolved instanceof $targetClass) {
            throw new LogicException(
                sprintf('Class-level transform must return instance of [%s].', $targetClass)
            );
        }

        return $resolved;
    }

    /**
     * Apply target/source property mapping metadata.
     *
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    public function applyPropertyMappings(mixed $source, object $target, array $attributes): array
    {
        if (! is_object($source)) {
            return $attributes;
        }

        $resolved = $this->applyTargetPropertyMappings($source, $target, $attributes);

        return $this->applySourcePropertyMappings($source, $target, $resolved);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    protected function applyTargetPropertyMappings(object $source, object $target, array $attributes): array
    {
        $resolved = $attributes;
        $reflection = new ReflectionClass($target);

        foreach ($reflection->getProperties() as $property) {
            $maps = $property->getAttributes(Map::class, ReflectionAttribute::IS_INSTANCEOF);

            foreach ($maps as $attribute) {
                $map = $attribute->newInstance();
                $targetKey = $property->getName();
                $sourceKey = is_string($map->source) && $map->source !== '' ? $map->source : $targetKey;
                $hasValue = array_key_exists($sourceKey, $attributes);
                $value = $hasValue ? $attributes[$sourceKey] : null;

                if (! $this->evaluateCondition($map->if, $value, $source, $target)) {
                    unset($resolved[$targetKey]);

                    continue;
                }

                if (! $hasValue) {
                    continue;
                }

                $resolved[$targetKey] = $this->applyTransform($map->transform, $value, $source, $target);
            }
        }

        return $resolved;
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    protected function applySourcePropertyMappings(object $source, object $target, array $attributes): array
    {
        $resolved = $attributes;
        $reflection = new ReflectionClass($source);

        foreach ($reflection->getProperties() as $property) {
            $maps = $property->getAttributes(Map::class, ReflectionAttribute::IS_INSTANCEOF);

            foreach ($maps as $attribute) {
                $map = $attribute->newInstance();
                $defaultSourceKey = $property->getName();
                $sourceKey = is_string($map->source) && $map->source !== '' ? $map->source : $defaultSourceKey;
                $targetKey = is_string($map->target) && $map->target !== '' ? $map->target : $defaultSourceKey;
                $hasValue = array_key_exists($sourceKey, $attributes);
                $value = $hasValue ? $attributes[$sourceKey] : null;

                if (! $this->evaluateCondition($map->if, $value, $source, $target)) {
                    unset($resolved[$targetKey]);

                    if ($sourceKey !== $targetKey) {
                        unset($resolved[$sourceKey]);
                    }

                    continue;
                }

                if (! $hasValue) {
                    continue;
                }

                $resolved[$targetKey] = $this->applyTransform($map->transform, $value, $source, $target);

                if ($sourceKey !== $targetKey) {
                    unset($resolved[$sourceKey]);
                }
            }
        }

        return $resolved;
    }

    protected function evaluateCondition(mixed $condition, mixed $value, object $source, ?object $target): bool
    {
        if ($condition === null) {
            return true;
        }

        if (is_bool($condition)) {
            return $condition;
        }

        $callable = $this->resolveCallable($condition);
        $result = $this->invokeCallable($callable, $value, $source, $target);

        return (bool) $result;
    }

    protected function applyTransform(mixed $transform, mixed $value, object $source, ?object $target): mixed
    {
        if ($transform === null) {
            return $value;
        }

        $callable = $this->resolveCallable($transform);

        return $this->invokeCallable($callable, $value, $source, $target);
    }

    /**
     * @return callable
     */
    protected function resolveCallable(mixed $definition): mixed
    {
        $resolved = $definition;

        if (is_string($resolved) && class_exists($resolved)) {
            $resolved = $this->container->make($resolved);
        }

        if (! is_callable($resolved)) {
            throw new LogicException('Map attribute "if" and "transform" values must be callable, class-string service, or bool for "if".');
        }

        return $resolved;
    }

    /**
     * @param callable $callable
     */
    protected function invokeCallable(callable $callable, mixed $value, object $source, ?object $target): mixed
    {
        if ($callable instanceof ConditionCallableInterface || $callable instanceof TransformCallableInterface) {
            return $callable($value, $source, $target);
        }

        if (is_string($callable) && function_exists($callable)) {
            return $callable($value);
        }

        $arguments = $this->buildArgumentsForCallable($callable, $value, $source, $target);

        return $callable(...$arguments);
    }

    /**
     * @param callable $callable
     *
     * @return array<int, mixed>
     */
    protected function buildArgumentsForCallable(callable $callable, mixed $value, object $source, ?object $target): array
    {
        $reflection = $this->reflectCallable($callable);

        if (! $reflection instanceof ReflectionFunctionAbstract) {
            return [$value];
        }

        if ($reflection->isVariadic()) {
            return [$value, $source, $target];
        }

        $parameterCount = $reflection->getNumberOfParameters();
        $requiredCount = $reflection->getNumberOfRequiredParameters();

        if ($requiredCount > 3) {
            throw new LogicException('Map callable cannot require more than 3 arguments.');
        }

        if ($parameterCount === 0) {
            return [];
        }

        if ($parameterCount === 1) {
            return [$value];
        }

        if ($parameterCount === 2) {
            return [$value, $source];
        }

        return [$value, $source, $target];
    }

    protected function reflectCallable(callable $callable): ?ReflectionFunctionAbstract
    {
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        if (is_object($callable)) {
            return new ReflectionMethod($callable, '__invoke');
        }

        if (is_string($callable) && str_contains($callable, '::')) {
            [$class, $method] = explode('::', $callable, 2);

            return new ReflectionMethod($class, $method);
        }

        if (is_string($callable) && function_exists($callable)) {
            return new ReflectionFunction($callable);
        }

        return null;
    }

    /**
     * @return array<int, Map>
     */
    protected function getClassMapAttributes(object $source): array
    {
        $reflection = new ReflectionClass($source);
        $attributes = $reflection->getAttributes(Map::class, ReflectionAttribute::IS_INSTANCEOF);

        return array_map(
            static fn (ReflectionAttribute $attribute): Map => $attribute->newInstance(),
            $attributes
        );
    }
}
