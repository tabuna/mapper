# Symfony Integration

`tabuna/map` can use the Symfony PSR-11 container directly.

## Bootstrap Once

```php
<?php

use Symfony\Component\DependencyInjection\ContainerInterface;
use Tabuna\Map\Mapper;

final class MapperBootstrap
{
    public function __construct(ContainerInterface $container)
    {
        Mapper::usePsrContainer($container);
    }
}
```

## Use Anywhere (No Per-Call Container)

```php
<?php

use Tabuna\Map\Mapper;

final class ImportAirportHandler
{
    public function __invoke(array $payload): AirportDto
    {
        return Mapper::map($payload)->to(AirportDto::class);
    }
}
```

## Collection Mapping

```php
$dtos = Mapper::map($payloads)->toMany(AirportDto::class);
```
