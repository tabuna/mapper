<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Support\Source\Contracts\ObjectPayloadExtractor;

class CustomSourceExtractorStub implements ObjectPayloadExtractor
{
    public function extract(object $source): ?array
    {
        if (! method_exists($source, 'payload')) {
            return null;
        }

        $payload = $source->payload();

        return is_array($payload) ? $payload : null;
    }
}
