<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
use AnzuSystems\SerializerBundle\Tests\Dto\AsymmetricVisibilityDto;
use AnzuSystems\SerializerBundle\Tests\Dto\BarDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ChildAccessorsDto;
use AnzuSystems\SerializerBundle\Tests\Dto\InheritedPrivateDto;
use AnzuSystems\SerializerBundle\Tests\Dto\InheritedPrivatePromotedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\InheritedPromotedNoArgumentDto;
use AnzuSystems\SerializerBundle\Tests\Dto\InheritedPromotedOverriddenDto;
use AnzuSystems\SerializerBundle\Tests\Dto\NonPublicAccessorsDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PlainNonPublicDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PrivateConstructorDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PrivateHookedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PromotedSetterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PublicPropertyDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyClassDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyNoConstructorDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyNotPromotedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyPromotedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\SerializedNameCollisionDto;
use AnzuSystems\SerializerBundle\Tests\Dto\SetHookDto;
use AnzuSystems\SerializerBundle\Tests\Dto\SetterWithoutGetterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ThrowingConstructorDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ThrowingGetterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\UninitializedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\UninitializedWithGetterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\VirtualGetOnlyDto;
use AnzuSystems\SerializerBundle\Tests\Dto\VirtualSetOnlyDto;
use InvalidArgumentException;
use LogicException;
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

    public function testConstructorFailureIsWrappedWithItsCause(): void
    {
        try {
            $this->serializer->deserialize('{"name":"anzu"}', ThrowingConstructorDto::class);
            self::fail('The constructor failure must throw.');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('Rejected anzu', $exception->getMessage());
            self::assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
        }
    }

    public function testPrivateConstructorIsWrapped(): void
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(PrivateConstructorDto::class);

        $this->serializer->deserialize('{"name":"anzu"}', PrivateConstructorDto::class);
    }

    /**
     * @param class-string $declaringClass
     */
    #[DataProvider('uninitialized')]
    public function testUninitializedPropertyNamesItself(object $dto, string $declaringClass, string $property): void
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(sprintf('"%s::$%s"', $declaringClass, $property));

        $this->serializer->serialize($dto);
    }

    public static function uninitialized(): iterable
    {
        yield 'read directly' => [new UninitializedDto(), UninitializedDto::class, 'direct'];
        yield 'read through a getter' => [new UninitializedWithGetterDto(), UninitializedWithGetterDto::class, 'accessed'];
        yield 'readonly, key missing in the input' => [new ReadonlyNoConstructorDto(), ReadonlyNoConstructorDto::class, 'id'];
    }

    public function testGetterFailureIsNotReportedAsUninitialized(): void
    {
        $this->expectException(LogicException::class);

        $this->serializer->serialize(new ThrowingGetterDto());
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

        yield 'private of a parent class, a child property of the same name wins' => [
            '{"direct":"anzu","accessed":" anzu ","shadowed":"child value","hidden":"ignored"}',
            InheritedPrivateDto::create('anzu', 'anzu', 'child value'),
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
            '{"count":3}',
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
