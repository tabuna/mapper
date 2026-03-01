<?php

namespace Tabuna\Map\Source\Extractors;

use Illuminate\Contracts\Support\Arrayable;
use Tabuna\Map\Source\Contracts\ObjectPayloadExtractor;

class ArrayableObjectExtractor implements ObjectPayloadExtractor
{
    public function extract(object $source): ?array
    {
        return $source instanceof Arrayable ? $source->toArray() : null;
    }
}
