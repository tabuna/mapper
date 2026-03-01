<?php

namespace Tabuna\Map\Container\Contracts;

interface KernelContainerProvider
{
    /**
     * Resolve framework container instance from kernel runtime.
     *
     * @return mixed
     */
    public function getContainer();
}
