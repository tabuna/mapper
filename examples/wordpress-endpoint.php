<?php

declare(strict_types=1);

use Tabuna\Map\Mapper;

$payload = [
    'code' => sanitize_text_field($_POST['code'] ?? ''),
    'city' => sanitize_text_field($_POST['city'] ?? ''),
];

$airport = Mapper::map($payload)->to(AirportDto::class);
