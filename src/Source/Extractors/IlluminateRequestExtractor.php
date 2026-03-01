<?php

namespace Tabuna\Map\Source\Extractors;

use Tabuna\Map\Source\Contracts\ObjectPayloadExtractor;
use Throwable;

class IlluminateRequestExtractor implements ObjectPayloadExtractor
{
    protected const REQUEST_CLASS = 'Illuminate\\Http\\Request';

    public function extract(object $source): ?array
    {
        $class = self::REQUEST_CLASS;

        if (! class_exists($class) || ! $source instanceof $class) {
            return null;
        }

        try {
            $resolved = $source->all();
        } catch (Throwable) {
            return null;
        }

        return is_array($resolved) ? $resolved : null;
    }
}
