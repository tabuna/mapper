<?php

namespace Tabuna\Map\Symfony;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tabuna\Map\Container\SymfonyContainerAdapter;
use Tabuna\Map\Mapper;

class TabunaMapBundle extends Bundle
{
    public function boot(): void
    {
        if ($this->container !== null) {
            Mapper::usePsrContainer(new SymfonyContainerAdapter($this->container));
        }
    }

    public function shutdown(): void
    {
        Mapper::resetContainer();

        parent::shutdown();
    }
}
