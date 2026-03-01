<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

class DummyAirportReadonlyDto
{
    public function __construct(
        public readonly string $code,
        public readonly string $city,
    ) {}
}
