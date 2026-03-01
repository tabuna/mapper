# PHP Mapper

A simple and elegant object mapper for PHP.
It maps arrays, JSON, requests, and collections into Eloquent models or plain classes with a Laravel-first API and framework-agnostic core.


To install, run:

```shell
composer require tabuna/map
```

## Documentation

- [Contributing](CONTRIBUTING.md)
- [Roadmap](ROADMAP.md)
- [Symfony integration](docs/integrations/symfony.md)
- [WordPress integration](docs/integrations/wordpress.md)

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

If you need full control over dependency resolution, use an explicit container:

```php
use Illuminate\Container\Container;

$container = new Container();

$airport = Mapper::mapUsingContainer($data, $container)
    ->to(Airport::class);
```

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

For a more explicit API, use `toMany()`:

```php
$airports = map($data)->toMany(Airport::class);
```

### JSON Input

The `map()` function also accepts a JSON string as input. 
It will automatically be decoded into an array before mapping:

```php
$json = '{"code": "LPK", "city": "Lipetsk"}';

$airport = map($json)->to(Airport::class);
```

### Customizing Mapping

You can override the default hydration logic by registering a custom mapper callback or an invokable class:

```php
$airport = map($data)
    ->with(function (array $item, Airport $target) {
        $target->code = strtoupper($item['code']);
        $target->city = $item['city'];

        return $target;
    })
    ->to(Airport::class);
```

Or via invoke class:

```php
$airport = map($data)
    ->with(CustomAirportMapper::class)
    ->to(Airport::class);
```

Custom mappers must return an object. If several mappers are registered, the first one is used.


### Serializing to Array or JSON

Any object created through the Mapper can be easily converted to an array or JSON:

```php
$array = map($airport)->toArray();
$json = map($airport)->toJson();
```

`toArray()` serializes public object properties and supports arrays / `Arrayable` sources.

### Quality Gates

- Tests must pass on supported PHP/Laravel matrix.
- Psalm static analysis must pass.
- Pint style checks must pass.
- Line coverage is enforced at 100% in CI.
