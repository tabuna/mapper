# Symfony Integration

`tabuna/map` can use Symfony container automatically.

## Register Bundle Once

```php
<?php

// config/bundles.php
return [
    // ...
    Tabuna\Map\Symfony\TabunaMapBundle::class => ['all' => true],
];
```

## Use Anywhere (No Per-Call Container)

```php
<?php

use Tabuna\Map\Mapper;

final class ImportAirportHandler
{
    public function __invoke(array $payload): AirportDto
    {
        return Mapper::into($payload, AirportDto::class);
    }
}
```

## Collection Mapping

```php
$dtos = Mapper::intoMany($payloads, AirportDto::class);
```

## Payload Key Alignment

```php
$dto = Mapper::map($payload)
    ->rename(['iata_code' => 'code'])
    ->snakeToCamelKeys()
    ->strict()
    ->to(AirportDto::class);
```

## Optional Explicit API

If you don't use bundle registration, you can still map in one call:

```php
$dto = Mapper::mapUsingPsrContainer($payload, $container)->to(AirportDto::class);
```
