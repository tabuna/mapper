<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Psr\Container\ContainerInterface;
use Tabuna\Map\Container\Contracts\KernelContainerProvider;

class KernelWithContainerStub implements KernelContainerProvider
{
    public function __construct(private ContainerInterface $container) {}

    public function getContainer()
    {
        return $this->container;
    }
}
