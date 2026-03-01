<?php

namespace Tabuna\Map\Source\Extractors;

use Tabuna\Map\Source\Contracts\ObjectPayloadExtractor;
use Throwable;

class EloquentModelAttributesExtractor implements ObjectPayloadExtractor
{
    protected const ELOQUENT_MODEL_CLASS = 'Illuminate\\Database\\Eloquent\\Model';

    public function extract(object $source): ?array
    {
        $modelClass = self::ELOQUENT_MODEL_CLASS;

        if (! class_exists($modelClass) || ! $source instanceof $modelClass) {
            return null;
        }

        try {
            $attributes = $source->attributesToArray();
        } catch (Throwable) {
            return null;
        }

        return is_array($attributes) ? $attributes : null;
    }
}
