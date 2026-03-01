<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerInterface;
use Tabuna\Map\Mapper;

final class MapperBootstrap
{
    public function __construct(ContainerInterface $container)
    {
        Mapper::usePsrContainer($container);
    }
}
