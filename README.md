# PHP Mapper

[![Tests](https://github.com/tabuna/map/actions/workflows/phpunit.yml/badge.svg)](https://github.com/tabuna/map/actions/workflows/phpunit.yml)
[![Psalm](https://github.com/tabuna/map/actions/workflows/psalm.yml/badge.svg)](https://github.com/tabuna/map/actions/workflows/psalm.yml)
[![Style](https://github.com/tabuna/map/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/tabuna/map/actions/workflows/php-cs-fixer.yml)
[![Coverage](https://github.com/tabuna/map/actions/workflows/coverage.yml/badge.svg)](https://github.com/tabuna/map/actions/workflows/coverage.yml)

A simple and elegant object mapper for PHP.
It maps arrays, JSON, requests, and collections into Eloquent models or plain classes with a Laravel-first API and framework-agnostic core.


To install, run:

```shell
composer require tabuna/map
```

## Documentation

- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Roadmap](ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Why tabuna/map](docs/comparison/why-tabuna-map.md)
- [Laravel integration](docs/integrations/laravel.md)
- [HTTP clients integration](docs/integrations/http-clients.md)
- [Symfony integration](docs/integrations/symfony.md)
- [WordPress integration](docs/integrations/wordpress.md)
- [Benchmarks](benchmarks/README.md)

## Why This Package

- Fast path from payload to object with low ceremony.
- Works for both Laravel models and plain PHP DTOs.
- Extensible via custom mappers only when you need special behavior.
- Keeps Laravel-specific HTTP/DB packages optional for lean Symfony/WordPress installs.
- Designed with strict quality gates for predictable upgrades.
- Includes reproducible benchmark script for performance comparisons.

## Internal Architecture

The public API stays minimal (`map()->to()`) while internals are separated by responsibility:

- `Support/ContainerResolver`: global + auto-detected container resolution.
- `Support/FrameworkContainerDetector`: isolated Laravel/Symfony/global runtime detection.
- `Support/SourceNormalizer`: request/object/JSON payload normalization via pluggable source extractors.
- `Support/AttributeRules`: `path`, `only`, `except`, `rename`, camel-case transforms.
- `Support/TargetFactory`: constructor-aware target instantiation.
- `Support/TargetHydrator`: filling + strict unknown-attribute validation.
- `Support/EloquentModelSupport`: isolated Eloquent-specific behavior.
- `Support/helpers.php`: only `map()` helper, no framework-specific runtime logic.

## Framework Auto Integration

- Laravel: container is wired automatically via package discovery.
- Symfony: use `Tabuna\Map\Symfony\TabunaMapBundle` once, then `map()->to` works everywhere.
- Custom runtimes: Mapper can auto-detect `$GLOBALS['kernel']` or `$GLOBALS['container']` when they expose a PSR-11 container.

### Mapping Data

The core function is `map()`, which accepts source data and returns a mapper instance for further transformation.
It works directly with arrays, JSON, Laravel/Symfony request objects, WP REST requests, and PSR-7 parsed-body requests.
For Laravel `FormRequest`, it prefers `validated()` (or `safe()->all()`) over raw `all()`.
For Symfony requests, it can read request/query bags without manual extraction.
For Laravel HTTP / Guzzle responses, mapper can read `json()`, `body()`, and `getBody()` payloads directly.

```php
use App\Http\Requests\StoreAirportRequest;
use App\Models\Airport;

class AirportController extends Controller
{
    public function store(StoreAirportRequest $request)
    {
        $airport = map($request)->to(Airport::class);

        $airport->save();

        return response()->json($airport);
    }
}
```

If you prefer one-shot calls, use `Mapper::into()` / `Mapper::intoMany()`:

```php
$airport = Mapper::into($request, Airport::class);
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

### Key Mapping (Value for Real Payloads)

Use `rename()` when payload keys don't match target fields:

```php
$airport = map(['iata' => 'LPK', 'location' => 'Lipetsk'])
    ->rename([
        'iata' => 'code',
        'location' => 'city',
    ])
    ->to(AirportDto::class);
```

Use `only()` / `except()` to control payload surface before mapping:

```php
$airport = map($payload)
    ->only(['iata', 'location'])
    ->rename(['iata' => 'code', 'location' => 'city'])
    ->to(AirportDto::class);
```

Use `path()` for nested API envelopes:

```php
$airport = map($payload)
    ->path('data.attributes')
    ->to(AirportDto::class);
```

Use `snakeToCamelKeys()` for snake_case / kebab-case payloads:

```php
$airport = map([
    'airport_code' => 'LPK',
    'city_name' => 'Lipetsk',
])->snakeToCamelKeys()->to(AirportDto::class);
```

Enable strict mode to fail fast on unexpected keys:

```php
map([
    'code' => 'LPK',
    'city' => 'Lipetsk',
    'extra' => 'unexpected',
])->strict()->to(AirportDto::class); // throws InvalidArgumentException
```

Framework request objects work without manual `->all()` extraction:

```php
$dto = map($symfonyRequest)->to(AirportDto::class);
$dto = map($wpRestRequest)->to(AirportDto::class);
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
- Tagged versions (`v*`) are published automatically via GitHub Releases.

### Benchmark

```bash
composer bench
```
