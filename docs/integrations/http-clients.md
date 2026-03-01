# HTTP Clients Integration

`tabuna/map` can map HTTP response objects directly without manual decode boilerplate.

## Laravel HTTP Client

```php
use Illuminate\Support\Facades\Http;

$response = Http::get('https://api.example.com/airports/LPK');

$airport = map($response)->to(AirportDto::class);
```

Mapper reads `json()` first, then falls back to `body()` JSON decoding.
Supported source class: `Illuminate\Http\Client\Response`.

## Guzzle / PSR-7 Response

```php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->get('https://api.example.com/airports/LPK');

$airport = map($response)->to(AirportDto::class);
```

Mapper reads `getBody()` stream and decodes JSON payload.
Supported source contract: `Psr\Http\Message\ResponseInterface`.

## cURL

```php
$ch = curl_init('https://api.example.com/airports/LPK');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$raw = curl_exec($ch);
curl_close($ch);

$airport = map($raw)->to(AirportDto::class);
```

Since `curl_exec()` returns string payload, JSON mapping works out of the box.
