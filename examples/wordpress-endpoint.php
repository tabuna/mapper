<?php

declare(strict_types=1);

$payload = [
    'code' => sanitize_text_field($_POST['code'] ?? ''),
    'city' => sanitize_text_field($_POST['city'] ?? ''),
];

$airport = map_into($payload, AirportDto::class);
