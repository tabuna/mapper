<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

class DummyAirportCamelReadonlyDto
{
    public function __construct(
        public readonly string $airportCode,
        public readonly string $cityName,
    ) {}
}
