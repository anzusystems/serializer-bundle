<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
use AnzuSystems\SerializerBundle\Tests\Dto\AsymmetricVisibilityDto;
use AnzuSystems\SerializerBundle\Tests\Dto\BarDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ChildAccessorsDto;
use AnzuSystems\SerializerBundle\Tests\Dto\InheritedConstructorChildDto;
use AnzuSystems\SerializerBundle\Tests\Dto\InheritedPrivateDto;
use AnzuSystems\SerializerBundle\Tests\Dto\InheritedPrivatePromotedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\InheritedPromotedNoArgumentDto;
use AnzuSystems\SerializerBundle\Tests\Dto\InheritedPromotedOverriddenDto;
use AnzuSystems\SerializerBundle\Tests\Dto\NonPublicAccessorsDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PlainNonPublicDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PrivateHookedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PrivateNameCollisionDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PromotedSetterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PublicPropertyDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyClassDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyConstructorParameterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyGetterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyLeftUnsetDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyNoConstructorDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyNotPromotedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyParameterOtherTypeDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyPromotedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlySetByConstructorDateDto;
use AnzuSystems\SerializerBundle\Tests\Dto\RedeclaredPromotedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\SerializedNameCollisionDto;
use AnzuSystems\SerializerBundle\Tests\Dto\SetHookDto;
use AnzuSystems\SerializerBundle\Tests\Dto\SetterWithoutGetterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\StaticPropertyDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ThrowingConstructorDto;
use AnzuSystems\SerializerBundle\Tests\Dto\UninitializedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\UninitializedWithGetterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\VirtualGetOnlyDto;
use AnzuSystems\SerializerBundle\Tests\Dto\VirtualSetOnlyDto;
use Error;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

final class PropertyAccessTest extends AbstractTestCase
{
    /**
     * @throws SerializerException
     */
    #[DataProvider('data')]
    public function testDeserializeAndSerialize(string $json, object $expected, string $expectedJson): void
    {
        self::assertEquals($expected, $this->serializer->deserialize($json, $expected::class));
        self::assertSame($expectedJson, $this->serializer->serialize($expected));
    }

    /**
     * @throws SerializerException
     */
    public function testMetadataCacheHoldsNoClosures(): void
    {
        $this->serializer->serialize(
            $this->serializer->deserialize('{"name":" anzu ","count":3}', PrivateHookedDto::class)
        );
        $classMetadata = self::getContainer()->get(MetadataRegistry::class)->get(PrivateHookedDto::class);

        self::assertEquals($classMetadata, unserialize(serialize($classMetadata)));
    }

    public function testStaticPropertyIsRefused(): void
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(StaticPropertyDto::class . '::$counter');

        $this->serializer->serialize(new StaticPropertyDto());
    }

    public function testConstructorExceptionPassesThroughUnchanged(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rejected anzu');

        $this->serializer->deserialize('{"name":"anzu"}', ThrowingConstructorDto::class);
    }

    #[DataProvider('uninitialized')]
    public function testUninitializedPropertyStaysAnError(object $dto, string $property): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage(sprintf('%s::$%s must not be accessed before initialization', $dto::class, $property));

        $this->serializer->serialize($dto);
    }

    public static function uninitialized(): iterable
    {
        yield 'read directly' => [new UninitializedDto(), 'direct'];
        yield 'read through a getter' => [new UninitializedWithGetterDto(), 'accessed'];
        yield 'readonly, key missing in the input' => [new ReadonlyNoConstructorDto(), 'id'];
    }

    public function testChildPropertyKeepsTheDataNameOfAParentsPrivateProperty(): void
    {
        $dto = $this->serializer->deserialize('{"id":"anzu"}', PrivateNameCollisionDto::class);

        self::assertSame('anzu', $dto->id);
        self::assertSame(1, $dto->getLegacyId());
        self::assertSame('{"id":"anzu"}', $this->serializer->serialize($dto));
    }

    public static function data(): iterable
    {
        yield 'private with getter and setter' => [
            '{"qux":1,"quux":"anzu"}',
            new BarDto(1, 'anzu'),
            '{"qux":1,"quux":"anzu"}',
        ];

        yield 'private and protected without accessors and hooks' => [
            '{"name":"anzu","count":3}',
            PlainNonPublicDto::create('anzu', 3),
            '{"name":"anzu","count":3}',
        ];

        $inheritedConstructorChild = new InheritedConstructorChildDto();
        $inheritedConstructorChild->name = 'anzu';

        yield 'inherited constructor promotes the private property of the parent, not the child property' => [
            '{"name":"anzu"}',
            $inheritedConstructorChild,
            '{"name":"anzu"}',
        ];

        yield 'private of a parent class, a child property of the same name wins' => [
            '{"direct":"anzu","accessed":" anzu ","shadowed":"child value","hidden":"ignored"}',
            InheritedPrivateDto::create(direct: 'anzu', accessed: 'anzu', shadowed: 'child value'),
            '{"shadowed":"child value","direct":"anzu","accessed":"anzu"}',
        ];

        yield 'private promoted by an inherited constructor' => [
            '{"name":"anzu"}',
            new InheritedPrivatePromotedDto('anzu'),
            '{"name":"anzu"}',
        ];

        yield 'protected of a parent class, accessors on the child' => [
            '{"name":" anzu ","readOnly":"ignored"}',
            ChildAccessorsDto::create('anzu'),
            '{"name":"ANZU","readOnly":"parent"}',
        ];

        $setterWithoutGetter = new SetterWithoutGetterDto();
        $setterWithoutGetter->setName('anzu');

        yield 'setter without a getter' => [
            '{"name":" anzu "}',
            $setterWithoutGetter,
            '{"name":"anzu"}',
        ];

        yield 'accessors that are not public' => [
            '{"name":"anzu"}',
            NonPublicAccessorsDto::create('anzu'),
            '{"name":"anzu"}',
        ];

        $public = new PublicPropertyDto();
        $public->name = 'anzu';

        yield 'public' => [
            '{"name":"anzu"}',
            $public,
            '{"name":"anzu"}',
        ];

        $setHook = new SetHookDto();
        $setHook->name = 'anzu';

        yield 'public with a set hook' => [
            '{"name":" anzu "}',
            $setHook,
            '{"name":"anzu"}',
        ];

        yield 'private(set) and protected(set), not promoted' => [
            '{"name":"anzu","count":3}',
            AsymmetricVisibilityDto::create('anzu', 3),
            '{"name":"anzu","count":3}',
        ];

        yield 'readonly, promoted' => [
            '{"name":"anzu","count":3}',
            new ReadonlyPromotedDto('anzu', 3),
            '{"name":"anzu","count":3}',
        ];

        yield 'readonly, set by the constructor, input ignored' => [
            '{"id":"ignored"}',
            new ReadonlyNotPromotedDto(),
            '{"id":"anzu"}',
        ];

        yield 'readonly, set by the constructor, invalid input ignored' => [
            '{"createdAt":"invalid"}',
            new ReadonlySetByConstructorDateDto(),
            '{"createdAt":"2026-01-01T00:00:00Z"}',
        ];

        yield 'readonly, constructor parameter of the same name takes the value' => [
            '{"limit":50}',
            new ReadonlyConstructorParameterDto(50),
            '{"limit":50}',
        ];

        yield 'readonly, constructor parameter of the same name, missing keeps the default' => [
            '{}',
            new ReadonlyConstructorParameterDto(),
            '{"limit":10}',
        ];

        yield 'readonly, constructor parameter of another type keeps its own type' => [
            '{"at":"2026-01-01T00:00:00Z"}',
            new ReadonlyParameterOtherTypeDto('2026-01-01T00:00:00Z'),
            '{"at":"2026-01-01T00:00:00Z"}',
        ];

        yield 'readonly, constructor leaves it unset, filled from the input' => [
            '{"id":"anzu"}',
            ReadonlyLeftUnsetDto::create('anzu'),
            '{"id":"anzu"}',
        ];

        yield 'private readonly with a getter and no setter, input ignored' => [
            '{"id":"ignored"}',
            new ReadonlyGetterDto(),
            '{"id":"constructor"}',
        ];

        yield 'protected promoted by the parent, declared again in the child' => [
            '{"count":3}',
            new RedeclaredPromotedDto(3),
            '{"count":3}',
        ];

        yield 'readonly, not set by a constructor, filled from the input' => [
            '{"id":"anzu"}',
            ReadonlyNoConstructorDto::create('anzu'),
            '{"id":"anzu"}',
        ];

        yield 'readonly class, filled from the input' => [
            '{"id":"anzu"}',
            ReadonlyClassDto::create('anzu'),
            '{"id":"anzu"}',
        ];

        yield 'inherited promoted, overridden by a parameter that is not promoted' => [
            '{"count":3}',
            new InheritedPromotedOverriddenDto(3),
            '{"count":3}',
        ];

        $inheritedNoArgument = new InheritedPromotedNoArgumentDto();
        $inheritedNoArgument->count = 3;

        yield 'inherited promoted, overridden without a parameter' => [
            '{"count":3}',
            $inheritedNoArgument,
            '{"count":3}',
        ];

        yield 'promoted with a setter, value' => [
            '{"count":-3}',
            new PromotedSetterDto(3),
            '{"count":3}',
        ];

        yield 'promoted with a setter, null keeps the default' => [
            '{"count":null}',
            new PromotedSetterDto(),
            '{"count":10}',
        ];

        yield 'data name of an optional property shared with a required parameter' => [
            '{"foo":"anzu"}',
            new SerializedNameCollisionDto('anzu'),
            '{"foo":"default"}',
        ];

        yield 'private and protected with hooks' => [
            '{"name":" anzu ","count":-3}',
            PrivateHookedDto::create('anzu', 0),
            '{"name":"anzu","count":0}',
        ];

        $virtualGetOnly = new VirtualGetOnlyDto();
        $virtualGetOnly->name = 'anzu';

        yield 'virtual, get only' => [
            '{"name":"anzu","label":"ignored"}',
            $virtualGetOnly,
            '{"name":"anzu","label":"ANZU"}',
        ];

        yield 'virtual, set only' => [
            '{"rawName":" anzu "}',
            VirtualSetOnlyDto::create('anzu'),
            '{"name":"anzu"}',
        ];
    }
}
