<?php

namespace Tabuna\Map\Support\Source\Extractors;

use Tabuna\Map\Support\Source\Contracts\ObjectPayloadExtractor;
use Throwable;

class MethodPayloadExtractor implements ObjectPayloadExtractor
{
    /**
     * @param array<int, string> $methods
     */
    public function __construct(protected array $methods) {}

    public function extract(object $source): ?array
    {
        foreach ($this->methods as $method) {
            if (! method_exists($source, $method)) {
                continue;
            }

            try {
                $resolved = $source->$method();
            } catch (Throwable) {
                continue;
            }

            if (is_array($resolved)) {
                return $resolved;
            }
        }

        return null;
    }
}
