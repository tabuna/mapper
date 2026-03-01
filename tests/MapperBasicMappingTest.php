<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use Illuminate\Support\Collection;
use Tabuna\Map\Mapper;
use Tabuna\Map\Tests\Dummy\DummyAirport;
use Tabuna\Map\Tests\Dummy\DummyAirportHook;
use Tabuna\Map\Tests\Dummy\DummyAirportReadonlyDto;
use Tabuna\Map\Tests\Dummy\DummyAirportReadonlyWithContainer;
use Tabuna\Map\Tests\Dummy\DummyAirportWithPrivateCode;
use Tabuna\Map\Tests\Dummy\DummyWithContainer;
use Tabuna\Map\Tests\Dummy\EloquentAirportStub;

class MapperBasicMappingTest extends MapperTestCase
{
    public function testItMapsArrayToObjectProperties(): void
    {
        $mapped = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk'])->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItMapsArrayToObjectHookProperties(): void
    {
        $this->markTestSkippedUnless(
            version_compare(PHP_VERSION, '8.4', '>'),
            'PHP version >= 8.4 or higher is required.'
        );

        $mapped = Mapper::map(['code' => 'lpk', 'city' => 'Lipetsk'])->to(DummyAirportHook::class);

        $this->assertInstanceOf(DummyAirportHook::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
    }

    public function testItMapsArrayToObjectWithOutPartProperties(): void
    {
        $mapped = Mapper::map(['code' => 'LPK'])->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
    }

    public function testItMapsCollectionOfArrays(): void
    {
        $data = [
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ];

        $mapped = Mapper::map($data)->collection()->to(DummyAirport::class);

        $this->assertInstanceOf(Collection::class, $mapped);
        $this->assertCount(2, $mapped);
        $this->assertSame('SVO', $mapped[1]->code);
    }

    public function testToManyMapsCollectionOfArrays(): void
    {
        $data = [
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ];

        $mapped = Mapper::map($data)->toMany(DummyAirport::class);

        $this->assertInstanceOf(Collection::class, $mapped);
        $this->assertCount(2, $mapped);
        $this->assertSame('SVO', $mapped[1]->code);
    }

    public function testItFillsEloquentModelAttributes(): void
    {
        $mapped = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk'])->to(EloquentAirportStub::class);

        $this->assertInstanceOf(EloquentAirportStub::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItMapsWithContainerProperties(): void
    {
        $mapped = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk'])->to(DummyWithContainer::class);

        $this->assertInstanceOf(DummyWithContainer::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
        $this->assertNotNull($mapped->version);
    }

    public function testItMapsOverriteConstructorProperties(): void
    {
        $mapped = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk', 'version' => 2])->to(DummyWithContainer::class);

        $this->assertInstanceOf(DummyWithContainer::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
        $this->assertSame(2, $mapped->version);
    }

    public function testItIgnoresExtraFields(): void
    {
        $mapped = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk', 'extra' => 'ignored'])->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertFalse(property_exists($mapped, 'extra'));
    }

    public function testItMapsReadonlyDtoUsingConstructorArguments(): void
    {
        $mapped = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk'])->to(DummyAirportReadonlyDto::class);

        $this->assertInstanceOf(DummyAirportReadonlyDto::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItMapsReadonlyDtoWithContainerDependency(): void
    {
        $mapped = Mapper::map(['code' => 'LED', 'city' => 'Saint Petersburg'])
            ->to(DummyAirportReadonlyWithContainer::class);

        $this->assertInstanceOf(DummyAirportReadonlyWithContainer::class, $mapped);
        $this->assertSame('LED', $mapped->code);
        $this->assertSame('Saint Petersburg', $mapped->city);
        $this->assertNotEmpty($mapped->version);
    }

    public function testItSkipsPrivateTargetPropertiesWithoutFailing(): void
    {
        $mapped = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk'])->to(DummyAirportWithPrivateCode::class);

        $this->assertInstanceOf(DummyAirportWithPrivateCode::class, $mapped);
        $this->assertSame('initial', $mapped->code());
        $this->assertSame('Lipetsk', $mapped->city);
    }
}
