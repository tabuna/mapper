<?php

namespace Tabuna\Map\Support\Source\Extractors;

use Tabuna\Map\Support\Source\Contracts\ObjectPayloadExtractor;
use Throwable;

class PropertyBagPayloadExtractor implements ObjectPayloadExtractor
{
    /**
     * @param array<int, string> $properties
     */
    public function __construct(protected array $properties) {}

    public function extract(object $source): ?array
    {
        foreach ($this->properties as $property) {
            if (! property_exists($source, $property)) {
                continue;
            }

            try {
                $bag = $source->$property;
            } catch (Throwable) {
                continue;
            }

            if (! is_object($bag) || ! method_exists($bag, 'all')) {
                continue;
            }

            try {
                $resolved = $bag->all();
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
