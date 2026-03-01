<?php

namespace Tabuna\Map\Container;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Tabuna\Map\Container\Contracts\SymfonyContainerLike;

class SymfonyContainerAdapter implements ContainerInterface
{
    protected const SYMFONY_CONTAINER_INTERFACE = 'Symfony\\Component\\DependencyInjection\\ContainerInterface';

    public function __construct(protected object $container)
    {
        if (! self::supports($container)) {
            throw new InvalidArgumentException(
                'Container object must implement Symfony container interface or '.SymfonyContainerLike::class.'.'
            );
        }
    }

    public static function supports(mixed $container): bool
    {
        if (! is_object($container)) {
            return false;
        }

        if ($container instanceof SymfonyContainerLike) {
            return true;
        }

        $interface = self::SYMFONY_CONTAINER_INTERFACE;

        return interface_exists($interface) && $container instanceof $interface;
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
