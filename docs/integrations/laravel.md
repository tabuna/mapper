# Laravel Integration

`tabuna/map` works with Laravel out of the box via package discovery.

## Before / After Examples

See side-by-side controller/service examples:

- [Laravel before/after guide](../comparison/laravel-before-after.md)

## FormRequest: Only Validated Data

For `Illuminate\Foundation\Http\FormRequest`, mapper uses validated payload first.
It tries `validated()` and then `safe()->all()`, so `map($request)->to(...)` maps only validated fields.

```php
use App\Http\Requests\StoreAirportRequest;
use App\Models\Airport;

final class AirportController
{
    public function store(StoreAirportRequest $request)
    {
        $airport = map($request)->to(Airport::class); // uses validated payload

        $airport->save();

        return response()->json($airport);
    }
}
```

## Validator Contract Support

Mapper can map directly from `Illuminate\Contracts\Validation\Validator` using `validated()` payload:

```php
$validator = Validator::make($request->all(), [
    'code' => ['required', 'string'],
    'city' => ['required', 'string'],
]);

$dto = map($validator)->to(AirportDto::class);
```

## Eloquent Model as Source

When source is an Eloquent model, mapper uses model attributes payload (not relation tree), which is safer for DTO mapping:

```php
$dto = map($airportModel)->to(AirportDto::class);
```

## One-shot API

```php
$airport = Mapper::into($request, Airport::class);
$airports = Mapper::intoMany($rows, Airport::class);
```

## Key Mapping Pipeline

```php
$airport = map($request)
    ->only(['iata', 'city_name'])
    ->rename(['iata' => 'code'])
    ->snakeToCamelKeys()
    ->strict()
    ->to(AirportDto::class);
```
