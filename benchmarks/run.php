<?php

declare(strict_types=1);

use Tabuna\Map\Mapper;

require __DIR__.'/../vendor/autoload.php';

const ITERATIONS = 50000;

/**
 * @param callable(): mixed $callback
 */
function benchmark(string $title, callable $callback): float
{
    $start = hrtime(true);
    $callback();
    $elapsed = hrtime(true) - $start;

    $ms = $elapsed / 1_000_000;
    printf("%-36s %10.2f ms\n", $title, $ms);

    return $ms;
}

$payload = [
    'code' => 'LPK',
    'city' => 'Lipetsk',
];

$payloadList = array_fill(0, ITERATIONS, $payload);

echo "tabuna/map benchmark\n";
echo 'Iterations: '.ITERATIONS."\n\n";

$manualObject = benchmark('Manual object mapping', static function () use ($payloadList): void {
    foreach ($payloadList as $row) {
        $obj = new stdClass();
        $obj->code = $row['code'];
        $obj->city = $row['city'];
    }
});

$mapperObject = benchmark('Mapper::into object mapping', static function () use ($payloadList): void {
    foreach ($payloadList as $row) {
        Mapper::into($row, BenchmarkAirportDto::class);
    }
});

$manualCollection = benchmark('Manual collection mapping', static function () use ($payloadList): void {
    $mapped = [];

    foreach ($payloadList as $row) {
        $mapped[] = new BenchmarkAirportDto($row['code'], $row['city']);
    }
});

$mapperCollection = benchmark('Mapper::intoMany collection', static function () use ($payloadList): void {
    Mapper::intoMany($payloadList, BenchmarkAirportDto::class);
});

echo "\nSummary\n";
printf("%-36s %10.2f ms\n", 'Object overhead vs manual', $mapperObject - $manualObject);
printf("%-36s %10.2f ms\n", 'Collection overhead vs manual', $mapperCollection - $manualCollection);

final class BenchmarkAirportDto
{
    public function __construct(
        public readonly string $code,
        public readonly string $city,
    ) {}
}
