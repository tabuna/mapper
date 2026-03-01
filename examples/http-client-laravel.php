<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

$response = Http::get('https://api.example.com/airports/LPK');

$airport = map($response)->to(AirportDto::class);
