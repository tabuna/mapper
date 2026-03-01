<?php

namespace Tabuna\Map\Support;

use Illuminate\Contracts\Support\Arrayable;
use Throwable;

class SourceNormalizer
{
    /**
     * Normalize top-level source before mapping starts.
     */
    public function prepareSource(mixed $source): mixed
    {
        return match (true) {
            $source instanceof Arrayable => $source->toArray(),
            is_string($source)           => json_decode($source, true, 512, JSON_THROW_ON_ERROR),
            default                      => $source,
        };
    }

    /**
     * Normalize any supported source item to an array of attributes.
     *
     * @param mixed $item
     *
     * @return array
     */
    public function normalizeAttributes(mixed $item): array
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
    public function normalizeObjectAttributes(object $item): array
    {
        $validated = $this->extractValidatedAttributes($item);

        if (is_array($validated)) {
            return $validated;
        }

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

        $bags = [
            'request',
            'query',
            'attributes',
        ];

        foreach ($bags as $bag) {
            $attributes = $this->extractAttributesFromPropertyBag($item, $bag);

            if (is_array($attributes)) {
                return $attributes;
            }
        }

        return get_object_vars($item);
    }

    /**
     * Try extracting validated payload from Laravel-style request objects.
     */
    public function extractValidatedAttributes(object $item): ?array
    {
        $validated = $this->extractAttributesFromMethod($item, 'validated');

        if (is_array($validated)) {
            return $validated;
        }

        if (! method_exists($item, 'safe')) {
            return null;
        }

        try {
            $safe = $item->safe();
        } catch (Throwable) {
            return null;
        }

        if (! is_object($safe) || ! method_exists($safe, 'all')) {
            return null;
        }

        try {
            $attributes = $safe->all();
        } catch (Throwable) {
            return null;
        }

        return is_array($attributes) ? $attributes : null;
    }

    /**
     * Try extracting array payload via a parameterless method.
     */
    public function extractAttributesFromMethod(object $item, string $method): ?array
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
     * Try extracting array payload from Symfony-like request bags.
     */
    public function extractAttributesFromPropertyBag(object $item, string $property): ?array
    {
        if (! property_exists($item, $property)) {
            return null;
        }

        try {
            $bag = $item->$property;
        } catch (Throwable) {
            return null;
        }

        if (! is_object($bag) || ! method_exists($bag, 'all')) {
            return null;
        }

        try {
            $attributes = $bag->all();
        } catch (Throwable) {
            return null;
        }

        return is_array($attributes) ? $attributes : null;
    }
}
