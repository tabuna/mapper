# Symfony Integration

`tabuna/map` can be used in Symfony services with `illuminate/container` as a lightweight resolver.

## Example

```php
<?php

use Illuminate\Container\Container;
use Tabuna\Map\Mapper;

final class ImportAirportHandler
{
    public function __invoke(array $payload): AirportDto
    {
        $container = new Container();

        return Mapper::mapUsingContainer($payload, $container)
            ->to(AirportDto::class);
    }
}
```

## Collection Mapping

```php
$dtos = Mapper::map($payloads)->toMany(AirportDto::class);
```
