<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\Dto\BarDto;
use AnzuSystems\SerializerBundle\Tests\Dto\BazDto;
use AnzuSystems\SerializerBundle\Tests\Dto\FooDto;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleBackedEnum;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleUnitEnum;
use DateTimeImmutable;

final class SerializeDeserializeBasicTest extends AbstractTestCase
{
    /**
     * @throws SerializerException
     *
     * @dataProvider data
     */
    public function testSerializeBasic(string $json, object $data): void
    {
        $serialized = $this->serializer->serialize($data);
        self::assertEquals($json, $serialized);
    }

    /**
     * @throws SerializerException
     *
     * @dataProvider dataIgnoreNulls
     */
    public function testSerializeIgnoreNulls(string $json, object $data): void
    {
        $serialized = $this->serializer->serialize($data, SerializationContext::create()->setSerializeNulls(false));
        self::assertEquals($json, $serialized);
    }

    /**
     * @throws SerializerException
     *
     * @dataProvider data
     */
    public function testDeSerializeBasic(string $json, object $data): void
    {
        $deserialized = $this->serializer->deserialize($json, $data::class);
        self::assertEquals($data, $deserialized);
    }

    /**
     * @throws SerializerException
     *
     * @dataProvider dataSerializeOnly
     */
    public function testSerializeOnly(string $expectedJson, string $json, object $data): void
    {
        $serialized = $this->serializer->serialize($data);
        self::assertEquals($expectedJson, $serialized);

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('Unable to deserialize "AnzuSystems\SerializerBundle\Tests\Dto\BazDto". Required constructor property "quux" missing in data or serializable properties');
        $this->serializer->deserialize($json, $data::class);
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
                ->setColor(ExampleUnitEnum::Red),
        ];

        yield [
            '{"id":2,"name":"Another","createdAt":"2022-12-31T00:00:00Z","place":"second","color":"Green"}',
            (new Example())
                ->setId(2)
                ->setName('Another')
                ->setCreatedAt(new DateTimeImmutable('2022-12-31T00:00:00Z'))
                ->setPlace(ExampleBackedEnum::Second)
                ->setColor(ExampleUnitEnum::Green),
        ];

        yield [
            '{"bar":123456,"baz":"bar-bar","bar-dto":{"qux":789333,"quux":"qux-qux"},"corge":"2024-05-10T00:00:00Z","garply":true,"fred":null}',
            (new FooDto(123_456, 'bar-bar', new BarDto(789_333, 'qux-qux')))
                ->setCorge(new DateTimeImmutable('2024-05-10T00:00:00Z'))
                ->setGarply(true),
        ];
    }

    public function dataIgnoreNulls(): iterable
    {
        yield [
            '{"bar":123456,"baz":"bar-bar","bar-dto":{"qux":789333,"quux":"qux-qux"},"corge":"2024-05-10T00:00:00Z","garply":true}',
            (new FooDto(123_456, 'bar-bar', new BarDto(789_333, 'qux-qux')))
                ->setCorge(new DateTimeImmutable('2024-05-10T00:00:00Z'))
                ->setGarply(true),
        ];
    }

    public function dataSerializeOnly(): iterable
    {
        yield [
            '{"qux":789333}',
            '{"qux":789333,"quux":"qux-qux"}',
            (new BazDto(789_333, 'qux-qux')),
        ];
    }
}
