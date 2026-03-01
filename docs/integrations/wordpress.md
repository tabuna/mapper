# WordPress Integration

You can use `tabuna/map` in plugin code to normalize request or option payloads into DTO-like objects.

## Example

```php
<?php

function create_airport(WP_REST_Request $request): AirportDto
{
    return map($request)->to(AirportDto::class);
}
```

## Mapping Arrays of Rows

```php
$rows = get_option('airports', []);
$airports = map($rows)->toMany(AirportDto::class);
```

## Mapping Different Key Formats

```php
$airport = map($request)
    ->path('airport')
    ->except(['legacy_field'])
    ->snakeToCamelKeys()
    ->strict()
    ->to(AirportDto::class);
```
