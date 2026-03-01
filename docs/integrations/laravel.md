# Laravel Integration

`tabuna/map` works with Laravel out of the box via package discovery.

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
