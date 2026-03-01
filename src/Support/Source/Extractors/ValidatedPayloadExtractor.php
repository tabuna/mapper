<?php

namespace Tabuna\Map\Support\Source\Extractors;

use Tabuna\Map\Support\Source\Contracts\ObjectPayloadExtractor;
use Throwable;

class ValidatedPayloadExtractor implements ObjectPayloadExtractor
{
    public function extract(object $source): ?array
    {
        $validated = $this->callMethodArray($source, 'validated');

        if (is_array($validated)) {
            return $validated;
        }

        if (! method_exists($source, 'safe')) {
            return null;
        }

        try {
            $safe = $source->safe();
        } catch (Throwable) {
            return null;
        }

        if (! is_object($safe)) {
            return null;
        }

        return $this->callMethodArray($safe, 'all');
    }

    /**
     * @return array|null
     */
    protected function callMethodArray(object $source, string $method): ?array
    {
        if (! method_exists($source, $method)) {
            return null;
        }

        try {
            $resolved = $source->$method();
        } catch (Throwable) {
            return null;
        }

        return is_array($resolved) ? $resolved : null;
    }
}
