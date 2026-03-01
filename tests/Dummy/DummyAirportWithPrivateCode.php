<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

class DummyAirportWithPrivateCode
{
    private string $code = 'initial';

    public string $city = '';

    public function code(): string
    {
        return $this->code;
    }
}
