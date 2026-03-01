<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Psr\Container\ContainerInterface;

class KernelWithContainerStub
{
    public function __construct(private ContainerInterface $container) {}

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
