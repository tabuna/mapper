<?php

declare(strict_types=1);

use Tabuna\Map\Mapper;

final class ImportAirportHandler
{
    public function __invoke(array $payload): AirportDto
    {
        return Mapper::into($payload, AirportDto::class);
    }
}
