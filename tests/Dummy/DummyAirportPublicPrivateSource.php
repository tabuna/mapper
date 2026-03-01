<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

class DummyAirportPublicPrivateSource
{
    public function __construct(
        public string $code,
        private string $secret,
    ) {}
}
