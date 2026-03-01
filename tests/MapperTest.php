<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tabuna\Map\Mapper;
use Tabuna\Map\Tests\Dummy\CustomMapperStub;
use Tabuna\Map\Tests\Dummy\DummyAirport;
use Tabuna\Map\Tests\Dummy\DummyAirportHook;
use Tabuna\Map\Tests\Dummy\DummyAirportPublicPrivateSource;
use Tabuna\Map\Tests\Dummy\DummyAirportWithPrivateCode;
use Tabuna\Map\Tests\Dummy\DummyWithContainer;
use Tabuna\Map\Tests\Dummy\EloquentAirportStub;
use Tabuna\Map\Tests\Dummy\InvalidMapperStub;
use Tabuna\Map\Tests\Dummy\KernelWithContainerStub;
use Tabuna\Map\Tests\Dummy\SimplePsrContainer;
use UnexpectedValueException;

class MapperTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['container'], $GLOBALS['kernel']);
        Mapper::resetContainer();

        parent::tearDown();
    }

    public function testItMapsArrayToObjectProperties(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)->to(DummyAirport::class);

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

        $data = ['code' => 'lpk', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)->to(DummyAirportHook::class);

        $this->assertInstanceOf(DummyAirportHook::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
    }

    public function testItMapsArrayToObjectWithOutPartProperties(): void
    {
        $data = ['code' => 'LPK'];

        $mapped = Mapper::map($data)->to(DummyAirport::class);

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

    public function testItMapsRequestToModel(): void
    {
        $request = Request::create('/fake', 'POST', [
            'code' => 'LPK',
            'city' => 'Lipetsk',
        ]);

        $mapped = Mapper::map($request)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItFillsEloquentModelAttributes(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)->to(EloquentAirportStub::class);

        $this->assertInstanceOf(EloquentAirportStub::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItUsesCustomMapperClass(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)
            ->with(CustomMapperStub::class)
            ->to(DummyAirport::class);

        $this->assertSame('custom-mapped', $mapped->code);
        $this->assertSame('custom-mapped', $mapped->city);
    }

    public function testItUsesCustomMapperClosure(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)
            ->with(function ($item, $target) {
                $this->assertInstanceOf(DummyAirport::class, $target);
                $this->assertSame('LPK', $item['code']);

                $obj = new DummyAirport();
                $obj->code = 'closure-mapped';
                $obj->city = 'closure-mapped';

                return $obj;
            })
            ->to(DummyAirport::class);

        $this->assertSame('closure-mapped', $mapped->code);
        $this->assertSame('closure-mapped', $mapped->city);
    }

    public function testItUsesCustomMapperForCollections(): void
    {
        $mapped = Mapper::map([['code' => 'LPK', 'city' => 'Lipetsk']])
            ->collection()
            ->with(function ($item, $target) {
                $target->code = strtolower($item['code']);
                $target->city = strtoupper($item['city']);

                return $target;
            })
            ->to(DummyAirport::class);

        $this->assertSame('lpk', $mapped->first()->code);
        $this->assertSame('LIPETSK', $mapped->first()->city);
    }

    public function testItThrowsWhenMapperIsNotCallable(): void
    {
        $this->expectException(LogicException::class);

        Mapper::map(['code' => 'LPK'])
            ->with(InvalidMapperStub::class)
            ->to(DummyAirport::class);
    }

    public function testItThrowsWhenMapperReturnsNonObject(): void
    {
        $this->expectException(UnexpectedValueException::class);

        Mapper::map(['code' => 'LPK'])
            ->with(fn () => 'invalid')
            ->to(DummyAirport::class);
    }

    public function testHelperFunctionMapsRequestToObject(): void
    {
        $request = Request::create('/fake', 'POST', [
            'code' => 'LED',
            'city' => 'Saint Petersburg',
        ]);

        $airport = map($request)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $airport);
        $this->assertSame('LED', $airport->code);
        $this->assertSame('Saint Petersburg', $airport->city);
    }

    public function testStaticIntoMapsSourceInOneShot(): void
    {
        $mapped = Mapper::into(['code' => 'LPK', 'city' => 'Lipetsk'], DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
    }

    public function testStaticIntoManyMapsCollectionInOneShot(): void
    {
        $mapped = Mapper::intoMany([
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ], DummyAirport::class);

        $this->assertInstanceOf(Collection::class, $mapped);
        $this->assertSame('SVO', $mapped[1]->code);
    }

    public function testItConvertsMappedObjectToArray(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $array = Mapper::map($data)->toArray();

        $this->assertIsArray($array);
        $this->assertSame(['code' => 'LPK', 'city' => 'Lipetsk'], $array);
    }

    public function testItConvertsMappedCollectionToArray(): void
    {
        $data = [
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ];

        $array = Mapper::map($data)->collection()->toArray();

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
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $json = Mapper::map($data)->toJson();

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
        $expected = json_encode($data, JSON_THROW_ON_ERROR);
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testCollectionModeReturnsLaravelCollection(): void
    {
        $data = [
            ['code' => 'LPK', 'city' => 'Lipetsk'],
            ['code' => 'SVO', 'city' => 'Moscow'],
        ];

        $result = Mapper::map($data)
            ->collection()
            ->to(DummyAirport::class);

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

    public function testItMapsWithContainerProperties(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)->to(DummyWithContainer::class);

        $this->assertInstanceOf(DummyWithContainer::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
        $this->assertNotNull($mapped->version);
    }

    public function testItMapsOverriteConstructorProperties(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk', 'version' => 2];

        $mapped = Mapper::map($data)->to(DummyWithContainer::class);

        $this->assertInstanceOf(DummyWithContainer::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
        $this->assertSame(2, $mapped->version);
    }

    public function testItIgnoresExtraFields(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk', 'extra' => 'ignored'];

        $mapped = Mapper::map($data)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertFalse(property_exists($mapped, 'extra'));
    }

    public function testItParsesValidJsonString(): void
    {
        $json = '{"code": "LPK", "city": "Lipetsk"}';

        $mapped = Mapper::map($json)->toArray();

        $this->assertIsArray($mapped);
        $this->assertSame('LPK', $mapped['code']);
        $this->assertSame('Lipetsk', $mapped['city']);
    }

    public function testItThrowsOnInvalidJson(): void
    {
        $this->expectException(\JsonException::class);

        $invalidJson = '{"code": "LPK", "city": "Lipetsk"';

        Mapper::map($invalidJson)->toArray();
    }

    public function testItSkipsPrivateTargetPropertiesWithoutFailing(): void
    {
        $data = ['code' => 'LPK', 'city' => 'Lipetsk'];

        $mapped = Mapper::map($data)->to(DummyAirportWithPrivateCode::class);

        $this->assertInstanceOf(DummyAirportWithPrivateCode::class, $mapped);
        $this->assertSame('initial', $mapped->code());
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testToArrayDoesNotExposePrivateSourceProperties(): void
    {
        $source = new DummyAirportPublicPrivateSource('LPK', 'hidden');

        $array = Mapper::map($source)->toArray();

        $this->assertSame(['code' => 'LPK'], $array);
        $this->assertArrayNotHasKey('secret', $array);
    }

    public function testItCanMapUsingExplicitContainer(): void
    {
        $container = new Container();
        $container->bind(DummyAirport::class, DummyAirportWithPrivateCode::class);

        $mapped = Mapper::mapUsingContainer(['city' => 'Lipetsk'], $container)
            ->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirportWithPrivateCode::class, $mapped);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItCanMapUsingExplicitPsrContainer(): void
    {
        $mapped = Mapper::mapUsingPsrContainer(['city' => 'Lipetsk'], new SimplePsrContainer([
            DummyAirport::class => new DummyAirportWithPrivateCode(),
        ]))->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirportWithPrivateCode::class, $mapped);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItCanUseGlobalIlluminateContainerWithoutPassingItEveryTime(): void
    {
        $container = new Container();
        $container->bind(DummyAirport::class, DummyAirportWithPrivateCode::class);

        Mapper::useContainer($container);

        $mapped = Mapper::map(['city' => 'Lipetsk'])->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirportWithPrivateCode::class, $mapped);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItCanUseGlobalPsrContainerWithoutPassingItEveryTime(): void
    {
        Mapper::usePsrContainer(new SimplePsrContainer([
            DummyAirport::class => new DummyAirportWithPrivateCode(),
        ]));

        $mapped = Mapper::map(['city' => 'Lipetsk'])->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirportWithPrivateCode::class, $mapped);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testResetContainerRemovesGlobalContainerConfiguration(): void
    {
        $container = new Container();
        $container->bind(DummyAirport::class, DummyAirportWithPrivateCode::class);

        Mapper::useContainer($container);
        Mapper::resetContainer();

        $mapped = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk'])->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('LPK', $mapped->code);
    }

    public function testItAutoDetectsContainerFromGlobalKernelWithoutManualSetup(): void
    {
        $GLOBALS['kernel'] = new KernelWithContainerStub(new SimplePsrContainer([
            DummyAirport::class => new DummyAirportWithPrivateCode(),
        ]));

        $mapped = Mapper::map(['city' => 'Lipetsk'])->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirportWithPrivateCode::class, $mapped);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItAutoDetectsContainerFromGlobalContainerVariableWithoutManualSetup(): void
    {
        $GLOBALS['container'] = new SimplePsrContainer([
            DummyAirport::class => new DummyAirportWithPrivateCode(),
        ]);

        $mapped = Mapper::map(['city' => 'Lipetsk'])->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirportWithPrivateCode::class, $mapped);
        $this->assertSame('Lipetsk', $mapped->city);
    }
}
