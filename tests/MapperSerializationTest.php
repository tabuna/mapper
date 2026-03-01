<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use JsonException;
use Tabuna\Map\Mapper;
use Tabuna\Map\Tests\Dummy\DummyAirport;

class MapperSerializationTest extends MapperTestCase
{
    public function testItConvertsMappedObjectToArray(): void
    {
        $array = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk'])->toArray();

        $this->assertIsArray($array);
        $this->assertSame(['code' => 'LPK', 'city' => 'Lipetsk'], $array);
    }

    public function testItConvertsMappedCollectionToArray(): void
    {
        $array = Mapper::map([
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ])->collection()->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertSame('Moscow', $array[1]['city']);
    }

    public function testCollectionModeThrowsForNonIterableSourceToObject(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Mapper::map(42)->collection()->to(DummyAirport::class);
    }

    public function testCollectionModeThrowsForNonIterableSourceToArray(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Mapper::map(42)->collection()->toArray();
    }

    public function testItConvertsMappedObjectToJson(): void
    {
        $json = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk'])->toJson();

        $this->assertJson($json);
        $this->assertJsonStringEqualsJsonString('{"code":"LPK","city":"Lipetsk"}', $json);
    }

    public function testItConvertsMappedCollectionToJson(): void
    {
        $data = [
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ];

        $json = Mapper::map($data)->collection()->toJson();

        $this->assertJson($json);
        $this->assertJsonStringEqualsJsonString(json_encode($data, JSON_THROW_ON_ERROR), $json);
    }

    public function testCollectionModeReturnsLaravelCollection(): void
    {
        $result = Mapper::map([
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ])->collection()->to(DummyAirport::class);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertContainsOnlyInstancesOf(DummyAirport::class, $result);
    }

    public function testItDoesNotWrapExistingCollectionIntoAnotherCollection(): void
    {
        $originalCollection = collect([
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ]);

        $mapped = Mapper::map($originalCollection)
            ->collection()
            ->to(DummyAirport::class);

        $this->assertInstanceOf(Collection::class, $mapped);
        $this->assertInstanceOf(DummyAirport::class, $mapped->first());
    }

    public function testItParsesValidJsonString(): void
    {
        $mapped = Mapper::map('{"code": "LPK", "city": "Lipetsk"}')->toArray();

        $this->assertIsArray($mapped);
        $this->assertSame('LPK', $mapped['code']);
        $this->assertSame('Lipetsk', $mapped['city']);
    }

    public function testItThrowsOnInvalidJson(): void
    {
        $this->expectException(JsonException::class);

        Mapper::map('{"code": "LPK", "city": "Lipetsk"')->toArray();
    }
}
