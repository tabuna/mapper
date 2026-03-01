<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use InvalidArgumentException;
use LogicException;
use stdClass;
use Tabuna\Map\Mapper;
use Tabuna\Map\Tests\Dummy\DummyAirport;
use Tabuna\Map\Tests\Dummy\MapAttrAdminUserProfile;
use Tabuna\Map\Tests\Dummy\MapAttrAmbiguousInput;
use Tabuna\Map\Tests\Dummy\MapAttrApiPayload;
use Tabuna\Map\Tests\Dummy\MapAttrEventInput;
use Tabuna\Map\Tests\Dummy\MapAttrLegacyUser;
use Tabuna\Map\Tests\Dummy\MapAttrLegacyUserData;
use Tabuna\Map\Tests\Dummy\MapAttrPhysicalEvent;
use Tabuna\Map\Tests\Dummy\MapAttrPrecedenceSource;
use Tabuna\Map\Tests\Dummy\MapAttrPrecedenceTarget;
use Tabuna\Map\Tests\Dummy\MapAttrProduct;
use Tabuna\Map\Tests\Dummy\MapAttrProductFromPayload;
use Tabuna\Map\Tests\Dummy\MapAttrProductInput;
use Tabuna\Map\Tests\Dummy\MapAttrPublicUserProfile;
use Tabuna\Map\Tests\Dummy\MapAttrUserEntity;

class MapperObjectMapperParityTest extends MapperTestCase
{
    public function testItMapsToExistingObjectInstance(): void
    {
        $input = new MapAttrProductInput();
        $input->customerEmail = 'new@example.com';
        $input->sku = 'abc-001';
        $input->internalNotes = 'skip';

        $target = new MapAttrProduct();
        $target->email = 'old@example.com';
        $target->code = 'OLD-000';
        $target->internalNotes = 'keep-me';

        $mapped = Mapper::map($input)->to($target);

        $this->assertSame($target, $mapped);
        $this->assertSame('new@example.com', $mapped->email);
        $this->assertSame('ABC-001', $mapped->code);
        $this->assertSame('keep-me', $mapped->internalNotes);
    }

    public function testCollectionModeThrowsWhenExistingObjectIsProvidedAsTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Mapper::map([['title' => 'Meetup']])
            ->collection()
            ->to(new MapAttrPhysicalEvent());
    }

    public function testItMapsFromStdClassSource(): void
    {
        $source = new stdClass();
        $source->code = 'HEL';
        $source->city = 'Helsinki';

        $mapped = Mapper::map($source)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('HEL', $mapped->code);
        $this->assertSame('Helsinki', $mapped->city);
    }

    public function testItInfersTargetClassFromClassLevelMapAttribute(): void
    {
        $input = new MapAttrProductInput();
        $input->customerEmail = 'user@example.com';
        $input->sku = 'sku-77';

        $mapped = Mapper::map($input)->to();

        $this->assertInstanceOf(MapAttrProduct::class, $mapped);
        $this->assertSame('user@example.com', $mapped->email);
        $this->assertSame('SKU-77', $mapped->code);
    }

    public function testItInfersTargetClassUsingClassLevelConditions(): void
    {
        $input = new MapAttrEventInput();
        $input->type = 'physical';
        $input->title = 'Offline Meetup';

        $mapped = Mapper::map($input)->to();

        $this->assertInstanceOf(MapAttrPhysicalEvent::class, $mapped);
        $this->assertSame('Offline Meetup', $mapped->title);
    }

    public function testItThrowsForAmbiguousClassLevelTargets(): void
    {
        $this->expectException(LogicException::class);

        $input = new MapAttrAmbiguousInput();
        $input->title = 'Ambiguous';

        Mapper::map($input)->to();
    }

    public function testItAppliesTargetPropertySourceAndTransformMappings(): void
    {
        $payload = new MapAttrApiPayload();
        $payload->product_name = 'Keyboard';
        $payload->price_amount = '$120';
        $payload->code = 'KB-001';

        $mapped = Mapper::map($payload)->to(MapAttrProductFromPayload::class);

        $this->assertSame('Keyboard', $mapped->name);
        $this->assertSame(120, $mapped->price);
        $this->assertSame('KB-001', $mapped->code);
    }

    public function testSourcePropertyMappingTakesPrecedenceOverTargetSourceMapping(): void
    {
        $source = new MapAttrPrecedenceSource();
        $source->legacyCode = 'SRC-01';

        $mapped = Mapper::map($source)->to(MapAttrPrecedenceTarget::class);

        $this->assertSame('SRC-01', $mapped->code);
    }

    public function testItSupportsTargetDependentMappingConditions(): void
    {
        $source = new MapAttrUserEntity();
        $source->lastLoginIp = '192.168.0.10';
        $source->registrationDate = '2024-01-15';

        $public = Mapper::map($source)->to(MapAttrPublicUserProfile::class);
        $admin = Mapper::map($source)->to(MapAttrAdminUserProfile::class);

        $this->assertSame('2024-01-15', $public->memberSince);
        $this->assertSame('2024-01-15', $admin->memberSince);
        $this->assertSame('192.168.0.10', $admin->ipAddress);
    }

    public function testItAppliesClassLevelTransformBeforeHydration(): void
    {
        $source = new MapAttrLegacyUserData();
        $source->userId = 42;
        $source->name = 'Alice';

        $mapped = Mapper::map($source)->to(MapAttrLegacyUser::class);

        $this->assertSame(42, $mapped->legacyId());
        $this->assertSame('Alice', $mapped->name);
    }
}
