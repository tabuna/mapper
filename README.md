# Laravel Mapper

A simple and elegant object mapper for Laravel.
It makes mapping arrays, requests, and collections into Eloquent models or any classes easy and convenient.


To install, run:

```shell
composer require tabuna/map
```

### Mapping Data

The core function is `map()`, which accepts source data and returns a mapper instance for further transformation.

```php
use Illuminate\Http\Request;
use App\Models\Airport;

class AirportController extends Controller
{
    public function store(Request $request)
    {
        $airport = map($request)->to(Airport::class);

        $airport->save();

        return response()->json($airport);
    }
}
```

The `to()` method creates a new instance of the target class and populates it with mapped data.
Only writable public properties are assigned for plain PHP objects (private, protected, static, and readonly properties are skipped).

### Mapping Collections

If the source data is an array or a collection, you can call `collection()` before `to()` to map each item individually:

```php
$data = [
    ['code' => 'LPK', 'city' => 'Lipetsk'],
    ['code' => 'JFK', 'city' => 'New York'],
];

$airports = map($data)
    ->collection()
    ->to(Airport::class);
```

This returns an `Illuminate\Support\Collection` of objects.

### JSON Input

The `map()` function also accepts a JSON string as input. 
It will automatically be decoded into an array before mapping:

```php
$json = '{"code": "LPK", "city": "Lipetsk"}';

$airport = map($json)->to(Airport::class);
```

<!--
### Customizing Mapping

By default, the Mapper will create objects even if some properties are missing. 
This is useful for incremental object building.
You can specify a custom mapper class or a closure to override default mapping behavior:

```php
$airport = map($data)
    ->with(fn ($mapper, $data) => new Airport([
        'code' => strtoupper($data['code'])
    ]))
    ->to(Airport::class);
```

Or via invoke class:

```php
$airport = map($data)
    ->with(CustomAirportMapper::class)
    ->to(Airport::class);
```
-->


### Serializing to Array or JSON

Any object created through the Mapper can be easily converted to an array or JSON:

```php
$array = map($airport)->toArray();
$json = map($airport)->toJson();
```

`toArray()` serializes public object properties and supports arrays / `Arrayable` sources.
