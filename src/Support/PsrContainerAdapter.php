<?php

namespace Tabuna\Map\Support;

use Illuminate\Container\Container;
use Psr\Container\ContainerInterface;

class PsrContainerAdapter extends Container
{
    public function __construct(protected ContainerInterface $container) {}

    /**
     * @param string|class-string $abstract
     *
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        if ($parameters !== []) {
            return parent::make($abstract, $parameters);
        }

        return $this->container->get($abstract);
    }

    /**
     * @param string|class-string $abstract
     */
    public function bound($abstract): bool
    {
        return $this->container->has($abstract);
    }
}
