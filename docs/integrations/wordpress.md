# WordPress Integration

You can use `tabuna/map` in plugin code to normalize request or option payloads into DTO-like objects.

## Example

```php
<?php

$payload = [
    'code' => sanitize_text_field($_POST['code'] ?? ''),
    'city' => sanitize_text_field($_POST['city'] ?? ''),
];

$airport = map($payload)->to(AirportDto::class);
```

## Mapping Arrays of Rows

```php
$rows = get_option('airports', []);
$airports = map($rows)->toMany(AirportDto::class);
```
