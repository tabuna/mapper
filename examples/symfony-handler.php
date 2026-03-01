<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Tabuna\Map\Mapper;

final class ImportAirportHandler
{
    public function __invoke(array $payload): AirportDto
    {
        $container = new Container();

        return Mapper::mapUsingContainer($payload, $container)
            ->to(AirportDto::class);
    }
}
