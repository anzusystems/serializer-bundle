<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
use AnzuSystems\SerializerBundle\OpenApi\SerializerModelDescriber;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyPromotedDto;
use AnzuSystems\SerializerBundle\Tests\Dto\VirtualGetOnlyDto;
use AnzuSystems\SerializerBundle\Tests\Dto\VirtualSetOnlyDto;
use AnzuSystems\SerializerBundle\Tests\Dto\VirtualSetOnlyNestedDto;
use Nelmio\ApiDocBundle\Model\Model;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\TypeInfo\Type;

final class SerializerModelDescriberTest extends AbstractTestCase
{
    /**
     * @param class-string $className
     */
    #[DataProvider('data')]
    public function testDescribeAccess(string $className, string $propertyName, bool $readOnly, bool $writeOnly): void
    {
        $describer = new SerializerModelDescriber(
            self::getContainer()->get(MetadataRegistry::class),
            self::getContainer()->get(HandlerResolver::class),
        );
        $schema = new Schema([]);
        $describer->describe(new Model(Type::object($className)), $schema);

        $properties = array_filter(
            $schema->properties,
            static fn (Property $property): bool => $propertyName === $property->property,
        );
        $property = array_first($properties);

        self::assertInstanceOf(Property::class, $property);
        self::assertSame($readOnly, true === $property->readOnly);
        self::assertSame($writeOnly, true === $property->writeOnly);
    }

    public static function data(): iterable
    {
        yield 'virtual, get only' => [VirtualGetOnlyDto::class, 'label', true, false];
        yield 'virtual, set only' => [VirtualSetOnlyDto::class, 'rawName', false, true];
        yield 'private(set), not promoted' => [VirtualSetOnlyDto::class, 'name', false, false];
        yield 'virtual, set only, nested object' => [VirtualSetOnlyNestedDto::class, 'rawBaz', false, true];
        yield 'readonly, promoted' => [ReadonlyPromotedDto::class, 'name', false, false];
    }
}
