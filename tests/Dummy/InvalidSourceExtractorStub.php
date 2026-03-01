<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

class InvalidSourceExtractorStub
{
    public function extract(object $source): ?array
    {
        return null;
    }
}
