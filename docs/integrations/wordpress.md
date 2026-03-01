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
$airport = map([
    'airport_code' => sanitize_text_field($_POST['airport_code'] ?? ''),
    'city_name' => sanitize_text_field($_POST['city_name'] ?? ''),
])->except(['legacy_field'])->snakeToCamelKeys()->strict()->to(AirportDto::class);
```
