# WordPress Integration

You can use `tabuna/map` in plugin code to normalize request or option payloads into DTO-like objects.

## Example

```php
<?php

use Tabuna\Map\Mapper;

$payload = [
    'code' => sanitize_text_field($_POST['code'] ?? ''),
    'city' => sanitize_text_field($_POST['city'] ?? ''),
];

$airport = map_into($payload, AirportDto::class);
```

## Mapping Arrays of Rows

```php
$rows = get_option('airports', []);
$airports = map_into_many($rows, AirportDto::class);
```
