<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use LogicException;
use Tabuna\Map\Mapper;
use Tabuna\Map\Tests\Dummy\CustomMapperStub;
use Tabuna\Map\Tests\Dummy\CustomSourceExtractorStub;
use Tabuna\Map\Tests\Dummy\DummyAirport;
use Tabuna\Map\Tests\Dummy\InvalidMapperStub;
use Tabuna\Map\Tests\Dummy\InvalidSourceExtractorStub;
use Tabuna\Map\Tests\Dummy\PayloadSourceStub;
use UnexpectedValueException;

class MapperCustomizationTest extends MapperTestCase
{
    public function testItUsesCustomMapperClass(): void
    {
        $mapped = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk'])
            ->with(CustomMapperStub::class)
            ->to(DummyAirport::class);

        $this->assertSame('custom-mapped', $mapped->code);
        $this->assertSame('custom-mapped', $mapped->city);
    }

    public function testItUsesCustomMapperClosure(): void
    {
        $mapped = Mapper::map(['code' => 'LPK', 'city' => 'Lipetsk'])
            ->with(function ($item, $target) {
                $this->assertInstanceOf(DummyAirport::class, $target);
                $this->assertSame('LPK', $item['code']);

                $target->code = 'closure-mapped';
                $target->city = 'closure-mapped';

                return $target;
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

    public function testItUsesCustomSourceExtractorInstance(): void
    {
        $mapped = Mapper::map(new PayloadSourceStub(['code' => 'MUC', 'city' => 'Munich']))
            ->withSourceExtractor(new CustomSourceExtractorStub())
            ->to(DummyAirport::class);

        $this->assertSame('MUC', $mapped->code);
        $this->assertSame('Munich', $mapped->city);
    }

    public function testItUsesCustomSourceExtractorByClassName(): void
    {
        $mapped = Mapper::map(new PayloadSourceStub(['code' => 'MAD', 'city' => 'Madrid']))
            ->withSourceExtractor(CustomSourceExtractorStub::class)
            ->to(DummyAirport::class);

        $this->assertSame('MAD', $mapped->code);
        $this->assertSame('Madrid', $mapped->city);
    }

    public function testItThrowsWhenSourceExtractorIsInvalid(): void
    {
        $this->expectException(LogicException::class);

        Mapper::map(['code' => 'LPK'])
            ->withSourceExtractor(InvalidSourceExtractorStub::class)
            ->to(DummyAirport::class);
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
}
