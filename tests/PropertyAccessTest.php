<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
use AnzuSystems\SerializerBundle\Tests\Dto\AsymmetricVisibilityDto;
use AnzuSystems\SerializerBundle\Tests\Dto\BarDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PrivateHookedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\PublicPropertyDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyPromotedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\SetHookDto;
use AnzuSystems\SerializerBundle\Tests\Dto\VirtualGetOnlyDto;
use AnzuSystems\SerializerBundle\Tests\Dto\VirtualSetOnlyDto;
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

    public static function data(): iterable
    {
        yield 'private with getter and setter' => [
            '{"qux":1,"quux":"anzu"}',
            new BarDto(1, 'anzu'),
            '{"qux":1,"quux":"anzu"}',
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
