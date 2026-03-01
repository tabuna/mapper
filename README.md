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
- [Changelog](CHANGELOG.md)
- [Why tabuna/map](docs/comparison/why-tabuna-map.md)
- [Symfony integration](docs/integrations/symfony.md)
- [WordPress integration](docs/integrations/wordpress.md)

## Why This Package

- Fast path from payload to object with low ceremony.
- Works for both Laravel models and plain PHP DTOs.
- Extensible via custom mappers only when you need special behavior.
- Designed with strict quality gates for predictable upgrades.

## Framework Auto Integration

- Laravel: container is wired automatically via package discovery.
- Symfony: use `Tabuna\Map\Symfony\TabunaMapBundle` once, then `map()->to` works everywhere.
- Custom runtimes: Mapper can auto-detect `$GLOBALS['kernel']` or `$GLOBALS['container']` when they expose a PSR-11 container.

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

If you prefer one-shot calls, use `Mapper::into()` / `Mapper::intoMany()`:

```php
$airport = Mapper::into($request->all(), Airport::class);
$airports = Mapper::intoMany($rows, Airport::class);
```

The `to()` method creates a new instance of the target class and populates it with mapped data.
Only writable public properties are assigned for plain PHP objects (private, protected, static, and readonly properties are skipped).
For immutable DTOs, constructor arguments are resolved from source keys (and class dependencies are still resolved from container).

```php
final class AirportDto
{
    public function __construct(
        public readonly string $code,
        public readonly string $city,
    ) {}
}

$airport = map(['code' => 'LPK', 'city' => 'Lipetsk'])->to(AirportDto::class);
```

If you want explicit one-shot PSR wiring, use:

```php
use Tabuna\Map\Mapper;

Mapper::mapUsingPsrContainer($payload, $symfonyContainer)
    ->to(AirportDto::class);
```

If your framework has its own container and you need explicit global setup:

```php
Mapper::usePsrContainer($symfonyContainer); // once during bootstrap
```

For one-off custom resolution (without global setup), use an explicit container:

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
