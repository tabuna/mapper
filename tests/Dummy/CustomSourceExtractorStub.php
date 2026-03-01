<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Source\Contracts\ObjectPayloadExtractor;

class CustomSourceExtractorStub implements ObjectPayloadExtractor
{
    public function extract(object $source): ?array
    {
        if (! $source instanceof PayloadSource) {
            return null;
        }

        $payload = $source->payload();

        return is_array($payload) ? $payload : null;
    }
}
