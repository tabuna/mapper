<?php

namespace Tabuna\Map\Symfony;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tabuna\Map\Mapper;
use Tabuna\Map\Support\SymfonyContainerAdapter;

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
