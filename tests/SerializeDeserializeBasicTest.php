<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleBackedEnum;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleUnitEnum;
use DateTimeImmutable;

final class SerializeDeserializeBasicTest extends AbstractTestCase
{
    /**
     * @throws SerializerException
     * @dataProvider data
     */
    public function testSerializeBasic(string $json, Example $data): void
    {
        $serialized = $this->serializer->serialize($data);
        self::assertEquals($json, $serialized);
    }

    /**
     * @throws SerializerException
     * @dataProvider data
     */
    public function testDeSerializeBasic(string $json, Example $data): void
    {
        $deserialized = $this->serializer->deserialize($json, $data::class);
        self::assertEquals($data, $deserialized);
    }

    public function data(): iterable
    {
        yield [
            '{"id":1,"name":"Test name","createdAt":"2023-12-31T12:34:56Z","place":"first","color":"Red"}',
            (new Example())
                ->setId(1)
                ->setName('Test name')
                ->setCreatedAt(new DateTimeImmutable('2023-12-31T12:34:56Z'))
                ->setPlace(ExampleBackedEnum::First)
                ->setColor(ExampleUnitEnum::Red)
        ];

        yield [
            '{"id":2,"name":"Another","createdAt":"2022-12-31T00:00:00Z","place":"second","color":"Green"}',
            (new Example())
                ->setId(2)
                ->setName('Another')
                ->setCreatedAt(new DateTimeImmutable('2022-12-31T00:00:00Z'))
                ->setPlace(ExampleBackedEnum::Second)
                ->setColor(ExampleUnitEnum::Green)
        ];
    }
}
