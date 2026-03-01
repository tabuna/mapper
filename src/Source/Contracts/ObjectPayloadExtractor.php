<?php

namespace Tabuna\Map\Source\Contracts;

interface ObjectPayloadExtractor
{
    /**
     * Try extracting array payload from object source.
     *
     * @return array|null
     */
    public function extract(object $source): ?array;
}
