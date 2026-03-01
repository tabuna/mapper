<?php

namespace Tabuna\Map\Support;

use function array_diff;
use function array_keys;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

class TargetHydrator
{
    /**
     * Optional Eloquent base model class used when Illuminate database package is installed.
     */
    protected const ELOQUENT_MODEL_CLASS = 'Illuminate\\Database\\Eloquent\\Model';

    /**
     * Fill the target object with data from the item.
     *
     * @param object $target
     * @param array  $attributes
     *
     * @return object
     */
    public function fill(object $target, array $attributes): object
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
     * Ensure strict mode has no unknown attributes for the target shape.
     *
     * @param class-string $targetClass
     * @param array        $attributes
     */
    public function assertNoUnknownAttributes(string $targetClass, object $target, array $attributes): void
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
     * Determine if a property can be assigned on the target object.
     */
    public function canAssignProperty(object $target, string $key): bool
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
     * Check if target is an Eloquent model without hard requiring Illuminate database package.
     */
    public function isEloquentModel(object $target): bool
    {
        $modelClass = self::ELOQUENT_MODEL_CLASS;

        return class_exists($modelClass)
            && $target instanceof $modelClass;
    }
}
