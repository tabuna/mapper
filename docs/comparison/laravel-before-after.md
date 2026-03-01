# Laravel: Before vs After `tabuna/map`

This page shows practical controller/service code without mapper ("before") and with mapper ("after").

## 1) FormRequest -> Model

Before:

```php
public function store(StoreAirportRequest $request)
{
    $data = $request->validated();

    $airport = new Airport();
    $airport->code = $data['code'];
    $airport->city = $data['city'];
    $airport->save();

    return response()->json($airport, 201);
}
```

After:

```php
public function store(StoreAirportRequest $request)
{
    $airport = map($request)->to(Airport::class); // validated()/safe()->all() is used automatically
    $airport->save();

    return response()->json($airport, 201);
}
```

## 2) Validator Contract -> DTO

Before:

```php
public function preview(Request $request): PreviewAirportDto
{
    $validator = Validator::make($request->all(), [
        'code' => ['required', 'string'],
        'city' => ['required', 'string'],
    ]);

    $data = $validator->validated();

    return new PreviewAirportDto(
        code: $data['code'],
        city: $data['city'],
    );
}
```

After:

```php
public function preview(Request $request): PreviewAirportDto
{
    $validator = Validator::make($request->all(), [
        'code' => ['required', 'string'],
        'city' => ['required', 'string'],
    ]);

    return map($validator)->to(PreviewAirportDto::class);
}
```

## 3) API Envelope + Key Renaming

Before:

```php
public function sync(array $payload): AirportDto
{
    $attributes = $payload['data']['attributes'] ?? [];

    return new AirportDto(
        code: $attributes['iata'] ?? '',
        city: $attributes['city_name'] ?? '',
    );
}
```

After:

```php
public function sync(array $payload): AirportDto
{
    return map($payload)
        ->path('data.attributes')
        ->rename(['iata' => 'code', 'city_name' => 'city'])
        ->strict()
        ->to(AirportDto::class);
}
```

## 4) Eloquent Model -> DTO (without relations leakage)

Before:

```php
public function show(Airport $airport): AirportDto
{
    return new AirportDto(
        code: $airport->code,
        city: $airport->city,
    );
}
```

After:

```php
public function show(Airport $airport): AirportDto
{
    return map($airport)->to(AirportDto::class); // attributesToArray(), relations are ignored
}
```
