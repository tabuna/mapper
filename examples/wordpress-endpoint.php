<?php

declare(strict_types=1);

function create_airport(WP_REST_Request $request): AirportDto
{
    return map($request)->to(AirportDto::class);
}
