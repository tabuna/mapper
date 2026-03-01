# Symfony ObjectMapper Parity

This document tracks practical feature parity between `tabuna/map` and Symfony ObjectMapper-style workflows.

## Implemented

- Map source object to new target class: `map($source)->to(Target::class)`.
- Map source object to existing target object: `map($source)->to($existingObject)`.
- Map from `stdClass`.
- Class-level default target via `#[Map(target: ...)]` and `map($source)->to()`.
- Multiple class targets with conditions via repeatable `#[Map(..., if: ...)]`.
- Property mapping with attributes:
  - `target` (source property -> target property),
  - `source` (target property <- source key),
  - `if` conditions,
  - `transform` value transformation.
- Condition and transform services via class-string resolvers.
- Target-dependent conditional mapping (condition service receives `$target`).
- Class-level transform before hydration.

## Examples

```php
use Tabuna\Map\Attribute\Map;

#[Map(target: UserEntity::class)]
final class UserInput
{
    #[Map(target: 'email')]
    public string $customerEmail = '';

    #[Map(if: false)]
    public string $internalNotes = '';
}

$entity = map($input)->to();          // infer target from class-level #[Map]
$entity = map($input)->to(UserEntity::class);
$entity = map($input)->to($existing); // update existing object
```

Target-side source mapping:

```php
#[Map(source: ExternalPayload::class)]
final class ProductDto
{
    #[Map(source: 'product_name')]
    public string $name = '';
}
```

## Remaining Advanced Items

- Built-in recursive graph mapping strategy (identity cache across deep cycles).
- Dedicated built-in collection transformer attribute (MapCollection-style).
- Decorator-oriented mapper state hooks equivalent to advanced Symfony decoration scenarios.

Current recommendation for these advanced cases: use `with(...)` custom mapper hooks.
