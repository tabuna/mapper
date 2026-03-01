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
}
