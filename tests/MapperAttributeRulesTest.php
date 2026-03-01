<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use InvalidArgumentException;
use Tabuna\Map\Mapper;
use Tabuna\Map\Tests\Dummy\DummyAirport;
use Tabuna\Map\Tests\Dummy\DummyAirportCamelDto;
use Tabuna\Map\Tests\Dummy\DummyAirportCamelReadonlyDto;
use Tabuna\Map\Tests\Dummy\EloquentAirportStub;

class MapperAttributeRulesTest extends MapperTestCase
{
    public function testItRenamesAttributesBeforeMapping(): void
    {
        $mapped = Mapper::map(['iata' => 'LPK', 'location' => 'Lipetsk'])
            ->rename([
                'iata'     => 'code',
                'location' => 'city',
            ])
            ->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testOnlyKeepsSelectedAttributes(): void
    {
        $mapped = Mapper::map([
            'code'  => 'LPK',
            'city'  => 'Lipetsk',
            'extra' => 'ignore',
        ])->only(['code', 'city'])->to(DummyAirport::class);

        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testExceptRemovesSelectedAttributes(): void
    {
        $array = Mapper::map([
            'code'  => 'LPK',
            'city'  => 'Lipetsk',
            'extra' => 'ignore',
        ])->except(['extra'])->toArray();

        $this->assertSame([
            'code' => 'LPK',
            'city' => 'Lipetsk',
        ], $array);
    }

    public function testOnlyAndRenameCanBeComposed(): void
    {
        $mapped = Mapper::map([
            'iata' => 'LPK',
            'city' => 'Lipetsk',
            'name' => 'unused',
        ])->only(['iata', 'city'])->rename([
            'iata' => 'code',
        ])->to(DummyAirport::class);

        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testPathExtractsNestedPayloadBeforeMapping(): void
    {
        $mapped = Mapper::map([
            'data' => [
                'attributes' => [
                    'code' => 'LPK',
                    'city' => 'Lipetsk',
                ],
            ],
        ])->path('data.attributes')->to(DummyAirport::class);

        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testPathReturnsEmptyPayloadWhenPathDoesNotExist(): void
    {
        $mapped = Mapper::map([
            'data' => [
                'attributes' => [
                    'code' => 'LPK',
                ],
            ],
        ])->path('data.missing')->toArray();

        $this->assertSame([], $mapped);
    }

    public function testItConvertsSnakeCaseKeysToCamelCaseForProperties(): void
    {
        $mapped = Mapper::map([
            'airport_code' => 'LPK',
            'city_name'    => 'Lipetsk',
        ])->snakeToCamelKeys()->to(DummyAirportCamelDto::class);

        $this->assertInstanceOf(DummyAirportCamelDto::class, $mapped);
        $this->assertSame('LPK', $mapped->airportCode);
        $this->assertSame('Lipetsk', $mapped->cityName);
    }

    public function testItConvertsSnakeCaseKeysToCamelCaseForReadonlyConstructor(): void
    {
        $mapped = Mapper::map([
            'airport_code' => 'LED',
            'city_name'    => 'Saint Petersburg',
        ])->snakeToCamelKeys()->to(DummyAirportCamelReadonlyDto::class);

        $this->assertInstanceOf(DummyAirportCamelReadonlyDto::class, $mapped);
        $this->assertSame('LED', $mapped->airportCode);
        $this->assertSame('Saint Petersburg', $mapped->cityName);
    }

    public function testStrictModeThrowsForUnknownAttributes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown attributes');

        Mapper::map([
            'code'  => 'LPK',
            'city'  => 'Lipetsk',
            'extra' => 'unexpected',
        ])->strict()->to(DummyAirport::class);
    }

    public function testStrictModeAllowsMappedAttributesAfterRenameAndCaseConversion(): void
    {
        $mapped = Mapper::map([
            'iata_code' => 'LPK',
            'city_name' => 'Lipetsk',
        ])->rename([
            'iata_code' => 'airport_code',
        ])->snakeToCamelKeys()->strict()->to(DummyAirportCamelReadonlyDto::class);

        $this->assertSame('LPK', $mapped->airportCode);
        $this->assertSame('Lipetsk', $mapped->cityName);
    }

    public function testStrictModeThrowsForUnknownEloquentAttributes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Mapper::map([
            'code'  => 'LPK',
            'city'  => 'Lipetsk',
            'extra' => 'unexpected',
        ])->strict()->to(EloquentAirportStub::class);
    }

    public function testToArrayAppliesRenameAndCaseRules(): void
    {
        $array = Mapper::map([
            'airport_code' => 'LPK',
            'city_title'   => 'Lipetsk',
        ])->rename([
            'city_title' => 'city_name',
        ])->snakeToCamelKeys()->toArray();

        $this->assertSame([
            'airportCode' => 'LPK',
            'cityName'    => 'Lipetsk',
        ], $array);
    }
}
