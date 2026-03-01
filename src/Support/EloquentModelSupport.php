<?php

namespace Tabuna\Map\Support;

use function array_diff;
use function array_keys;

use InvalidArgumentException;

class EloquentModelSupport
{
    /**
     * Optional Eloquent base model class used when Illuminate database package is installed.
     */
    protected const ELOQUENT_MODEL_CLASS = 'Illuminate\\Database\\Eloquent\\Model';

    /**
     * Try hydrating model attributes.
     *
     * @param object $target
     * @param array  $attributes
     *
     * @return object|null
     */
    public function fill(object $target, array $attributes): ?object
    {
        if (! $this->isEloquentModel($target)) {
            return null;
        }

        return $target->fill($attributes);
    }

    /**
     * Validate strict-mode unknown attributes for Eloquent models.
     *
     * @param class-string $targetClass
     * @param array        $attributes
     *
     * @return bool True when target is Eloquent model (validation handled).
     */
    public function validateStrictAttributes(string $targetClass, object $target, array $attributes): bool
    {
        if (! $this->isEloquentModel($target)) {
            return false;
        }

        $keys = array_values(array_filter(array_keys($attributes), 'is_string'));

        if ($keys === []) {
            return true;
        }

        $fillable = $target->getFillable();

        if ($fillable === []) {
            return true;
        }

        $unknown = array_values(array_diff($keys, $fillable));

        if ($unknown === []) {
            return true;
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
    public function isEloquentModel(object $target): bool
    {
        $modelClass = self::ELOQUENT_MODEL_CLASS;

        return class_exists($modelClass)
            && $target instanceof $modelClass;
    }
}
