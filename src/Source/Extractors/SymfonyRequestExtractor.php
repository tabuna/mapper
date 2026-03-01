<?php

namespace Tabuna\Map\Source\Extractors;

use Tabuna\Map\Source\Contracts\ObjectPayloadExtractor;
use Throwable;

class SymfonyRequestExtractor implements ObjectPayloadExtractor
{
    protected const REQUEST_CLASS = 'Symfony\\Component\\HttpFoundation\\Request';

    public function extract(object $source): ?array
    {
        $class = self::REQUEST_CLASS;

        if (! class_exists($class) || ! $source instanceof $class) {
            return null;
        }

        foreach (['request', 'query', 'attributes'] as $bag) {
            $attributes = $this->extractBag($source, $bag);

            if (is_array($attributes)) {
                return $attributes;
            }
        }

        return null;
    }

    protected function extractBag(object $source, string $bag): ?array
    {
        try {
            $parameterBag = $source->$bag;
        } catch (Throwable) {
            return null;
        }

        if (! is_object($parameterBag)) {
            return null;
        }

        try {
            $resolved = $parameterBag->all();
        } catch (Throwable) {
            return null;
        }

        return is_array($resolved) ? $resolved : null;
    }
}
