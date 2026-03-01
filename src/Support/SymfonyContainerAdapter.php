<?php

namespace Tabuna\Map\Support;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

class SymfonyContainerAdapter implements ContainerInterface
{
    public function __construct(protected object $container)
    {
        if (! method_exists($container, 'get') || ! method_exists($container, 'has')) {
            throw new InvalidArgumentException('Container object must expose get(string $id) and has(string $id): bool methods.');
        }
    }

    /**
     * @return mixed
     */
    public function get(string $id)
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return (bool) $this->container->has($id);
    }
}
