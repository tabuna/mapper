<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use Illuminate\Container\Container;
use Tabuna\Map\Mapper;
use Tabuna\Map\Tests\Dummy\DummyAirport;
use Tabuna\Map\Tests\Dummy\DummyAirportWithPrivateCode;
use Tabuna\Map\Tests\Dummy\KernelWithContainerStub;
use Tabuna\Map\Tests\Dummy\SimplePsrContainer;
use Tabuna\Map\Tests\Dummy\SimpleSymfonyContainerStub;

class MapperContainerIntegrationTest extends MapperTestCase
{
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

    public function testItAutoDetectsSymfonyLikeContainerFromGlobalContainerVariable(): void
    {
        $GLOBALS['container'] = new SimpleSymfonyContainerStub([
            DummyAirport::class => new DummyAirportWithPrivateCode(),
        ]);

        $mapped = Mapper::map(['city' => 'Lipetsk'])->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirportWithPrivateCode::class, $mapped);
        $this->assertSame('Lipetsk', $mapped->city);
    }
}
