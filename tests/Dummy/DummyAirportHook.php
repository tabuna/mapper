<?php

namespace Tabuna\Map\Tests\Dummy;

class DummyAirportHook
{
    public string $code = '' {
        get {
            return $this->code;
        }
        set(string $value) {
            $this->code = strtoupper($value);
        }
    }
}
