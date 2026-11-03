<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\Dto\ConstructorDefaultsDto;
use PHPUnit\Framework\Attributes\DataProvider;

final class ConstructorDefaultsTest extends AbstractTestCase
{
    /**
     * @throws SerializerException
     */
    #[DataProvider('validData')]
    public function testDeserialize(string $json, ConstructorDefaultsDto $expected): void
    {
        self::assertEquals($expected, $this->serializer->deserialize($json, ConstructorDefaultsDto::class));
    }

    /**
     * @throws SerializerException
     */
    #[DataProvider('invalidData')]
    public function testDeserializeWithoutRequiredArgument(string $json, string $missingArgument): void
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage(sprintf(
            'Unable to deserialize "%s". Required constructor property "%s" missing in data or serializable properties.',
            ConstructorDefaultsDto::class,
            $missingArgument,
        ));

        $this->serializer->deserialize($json, ConstructorDefaultsDto::class);
    }

    public static function validData(): iterable
    {
        yield 'every argument' => [
            '{"name":"anzu","label":"label","count":3,"note":"note"}',
            new ConstructorDefaultsDto(name: 'anzu', label: 'label', count: 3, note: 'note'),
        ];

        yield 'optional arguments missing' => [
            '{"name":"anzu","label":null}',
            new ConstructorDefaultsDto(name: 'anzu', label: null),
        ];

        yield 'null for a non-nullable optional argument' => [
            '{"name":"anzu","label":"label","count":null}',
            new ConstructorDefaultsDto(name: 'anzu', label: 'label', count: ConstructorDefaultsDto::DEFAULT_COUNT),
        ];

        yield 'null for a nullable optional argument' => [
            '{"name":"anzu","label":"label","note":null}',
            new ConstructorDefaultsDto(name: 'anzu', label: 'label', note: null),
        ];
    }

    public static function invalidData(): iterable
    {
        yield 'required argument missing' => ['{"label":"label"}', 'name'];
        yield 'null for a non-nullable required argument' => ['{"name":null,"label":"label"}', 'name'];
        yield 'nullable required argument missing' => ['{"name":"anzu"}', 'label'];
    }
}
