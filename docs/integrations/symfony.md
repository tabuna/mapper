# Symfony Integration

`tabuna/map` can use Symfony container automatically.

## ObjectMapper-Style Features

`tabuna/map` now supports the same core day-to-day workflows:

- map to new class;
- map to existing object;
- infer target from `#[Tabuna\Map\Attribute\Map(target: ...)]`;
- property-level `source` / `target` / `if` / `transform` attribute rules.

See parity notes: [ObjectMapper parity](../comparison/object-mapper-parity.md).

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
use Symfony\Component\HttpFoundation\Request;

final class ImportAirportHandler
{
    public function __invoke(Request $request): AirportDto
    {
        return Mapper::into($request, AirportDto::class);
    }
}
```

`Mapper::into($request, ...)` works with JSON payloads (`toArray()`), form payload bags (`$request->request->all()`), and query bags.

Update existing object:

```php
$entity = $repository->find($id);

map($dto)->to($entity);
```

## Collection Mapping

```php
$dtos = Mapper::intoMany($payloads, AirportDto::class);
```

## Payload Key Alignment

```php
$dto = Mapper::map($payload)
    ->path('data.attributes')
    ->only(['iata_code', 'city_name'])
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
