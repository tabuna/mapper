<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Illuminate\Container\Container;

class DummyAirportReadonlyWithContainer
{
    public function __construct(
        Container $container,
        public readonly string $code,
        public readonly string $city,
    ) {
        $this->version = $container::class;
    }

    public readonly string $version;
}
