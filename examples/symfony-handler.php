<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Tabuna\Map\Mapper;

final class ImportAirportHandler
{
    public function __invoke(Request $request): AirportDto
    {
        return Mapper::into($request, AirportDto::class);
    }
}
