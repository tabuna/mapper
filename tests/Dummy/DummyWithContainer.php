<?php

namespace Tabuna\Map\Tests\Dummy;

use Illuminate\Container\Container;

class DummyWithContainer extends DummyAirport
{
    public $version;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->version = $container::class;
    }
}
