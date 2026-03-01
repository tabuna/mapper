<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use Orchestra\Testbench\TestCase;
use Tabuna\Map\Mapper;

abstract class MapperTestCase extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['container'], $GLOBALS['kernel']);
        Mapper::resetContainer();

        parent::tearDown();
    }
}
