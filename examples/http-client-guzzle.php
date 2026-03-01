<?php

declare(strict_types=1);

use GuzzleHttp\Client;

$client = new Client();
$response = $client->get('https://api.example.com/airports/LPK');

$airport = map($response)->to(AirportDto::class);
