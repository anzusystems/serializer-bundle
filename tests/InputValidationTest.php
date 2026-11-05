<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\DeserializationException;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\Dto\MethodNamesDto;
use AnzuSystems\SerializerBundle\Tests\Dto\NonScalarTypesDto;
use AnzuSystems\SerializerBundle\Tests\Dto\NullableParameterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ReadonlyNullableParameterDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ScalarTypesDto;
use AnzuSystems\SerializerBundle\Tests\Dto\ScalarUnionDto;
use AnzuSystems\SerializerBundle\Tests\Dto\WiderSetHookDto;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleBackedEnum;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleIntBackedEnum;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleUnitEnum;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Stringable;
use Symfony\Component\Uid\MaxUuid;
use Symfony\Component\Uid\NilUuid;
use Symfony\Component\Uid\Uuid;

final class InputValidationTest extends AbstractTestCase
{
    /**
     * @throws SerializerException
     */
    #[DataProvider('acceptedScalars')]
    public function testAcceptedScalarIsConverted(string $json, string $property, mixed $expected): void
    {
        $dto = $this->serializer->deserialize($json, ScalarTypesDto::class);

        self::assertSame($expected, $dto->{$property});
    }

    public static function acceptedScalars(): iterable
    {
        yield 'int' => ['{"int":12}', 'int', 12];
        yield 'numeric string to int' => ['{"int":"12"}', 'int', 12];
        yield 'float to int is truncated' => ['{"int":12.7}', 'int', 12];
        yield 'empty string to int' => ['{"int":""}', 'int', 0];
        yield 'null to int' => ['{"int":null}', 'int', 0];
        yield 'bool to int' => ['{"int":true}', 'int', 1];
        yield 'numeric string to float' => ['{"float":"1.5"}', 'float', 1.5];
        yield 'int to float' => ['{"float":3}', 'float', 3.0];
        yield 'empty string to float' => ['{"float":""}', 'float', 0.0];
        yield 'int to string' => ['{"string":5}', 'string', '5'];
        yield 'bool to string' => ['{"string":true}', 'string', '1'];
        yield 'null to string' => ['{"string":null}', 'string', ''];
        yield 'string to bool' => ['{"bool":"true"}', 'bool', true];
        yield 'off to bool' => ['{"bool":"off"}', 'bool', false];
        yield 'number to bool' => ['{"bool":0}', 'bool', false];
        yield 'empty string to bool' => ['{"bool":""}', 'bool', false];
        yield 'null to bool' => ['{"bool":null}', 'bool', false];
        yield 'bool to bool' => ['{"bool":false}', 'bool', false];
        yield 'one to bool' => ['{"bool":1}', 'bool', true];
        yield 'yes to bool' => ['{"bool":"yes"}', 'bool', true];
        yield 'no to bool' => ['{"bool":"no"}', 'bool', false];
        yield 'on to bool' => ['{"bool":"on"}', 'bool', true];
        yield 'false to bool' => ['{"bool":"false"}', 'bool', false];
        yield 'null to float' => ['{"float":null}', 'float', 0.0];
        yield 'bool to float' => ['{"float":true}', 'float', 1.0];
    }

    /**
     * @throws SerializerException
     */
    public function testStringableIsConvertedToString(): void
    {
        $stringable = new class() implements Stringable {
            public function __toString(): string
            {
                return 'anzu';
            }
        };

        self::assertSame('anzu', $this->serializer->fromArray(['string' => $stringable], ScalarTypesDto::class)->string);
    }

    /**
     * @param class-string<Uuid> $expectedClass
     *
     * @throws SerializerException
     */
    #[DataProvider('acceptedUuids')]
    public function testAcceptedUuid(string $value, string $expectedClass, string $expected): void
    {
        $uuid = $this->serializer->fromArray(['uuid' => $value], NonScalarTypesDto::class)->uuid;

        self::assertInstanceOf($expectedClass, $uuid);
        self::assertSame($expected, $uuid->toRfc4122());
    }

    public static function acceptedUuids(): iterable
    {
        $common = '0192c5e0-7a0b-7c3e-9d11-2f7f7a3b1c11';
        $nil = '00000000-0000-0000-0000-000000000000';
        $max = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

        yield 'common' => [$common, Uuid::class, $common];
        yield 'nil' => [$nil, NilUuid::class, $nil];
        yield 'empty text is nil' => ['', NilUuid::class, $nil];
        yield 'max in capital letters' => [strtoupper($max), MaxUuid::class, $max];
        yield 'seed value outside the RFC variant' => ['00000000-0000-0000-0000-000000000001', Uuid::class, '00000000-0000-0000-0000-000000000001'];
        yield 'repeated digit' => ['11111111-1111-1111-1111-111111111111', Uuid::class, '11111111-1111-1111-1111-111111111111'];
        yield 'variant c' => ['0192c5e0-7a0b-7c3e-c111-2f7f7a3b1c11', Uuid::class, '0192c5e0-7a0b-7c3e-c111-2f7f7a3b1c11'];
        yield 'base58' => ['9in6hao2QUaGpwP4TvAESg', Uuid::class, Uuid::fromString('9in6hao2QUaGpwP4TvAESg')->toRfc4122()];
    }

    /**
     * @throws SerializerException
     */
    public function testAcceptedEnumsAndDate(): void
    {
        $dto = $this->serializer->deserialize(
            '{"enum":"second","intEnum":5,"unitEnum":"Blue","date":"2026-01-02T03:04:05Z"}',
            NonScalarTypesDto::class,
        );

        self::assertSame(ExampleBackedEnum::Second, $dto->enum);
        self::assertSame(ExampleIntBackedEnum::Five, $dto->intEnum);
        self::assertSame(ExampleUnitEnum::Blue, $dto->unitEnum);
        self::assertEquals(new DateTimeImmutable('2026-01-02T03:04:05Z'), $dto->date);
    }

    /**
     * @throws SerializerException
     */
    public function testIntBackedEnumTakesANumericString(): void
    {
        self::assertSame(
            ExampleIntBackedEnum::Five,
            $this->serializer->deserialize('{"intEnum":"5"}', NonScalarTypesDto::class)->intEnum,
        );
    }

    /**
     * @param class-string $className
     *
     * @throws SerializerException
     */
    #[DataProvider('refusedValues')]
    public function testRefusedValueNamesTheProperty(string $className, string $json, string $property): void
    {
        $this->expectException(DeserializationException::class);
        $this->expectExceptionMessage(sprintf('"%s::$%s"', $className, $property));

        $this->serializer->deserialize($json, $className);
    }

    public static function refusedValues(): iterable
    {
        yield 'text to int' => [ScalarTypesDto::class, '{"int":"abc"}', 'int'];
        yield 'text after a number to int' => [ScalarTypesDto::class, '{"int":"12abc"}', 'int'];
        yield 'array to int' => [ScalarTypesDto::class, '{"int":[1]}', 'int'];
        yield 'text to float' => [ScalarTypesDto::class, '{"float":"abc"}', 'float'];
        yield 'array to float' => [ScalarTypesDto::class, '{"float":[1]}', 'float'];
        yield 'array to string' => [ScalarTypesDto::class, '{"string":["a"]}', 'string'];
        yield 'text to bool' => [ScalarTypesDto::class, '{"bool":"maybe"}', 'bool'];
        yield 'array to bool' => [ScalarTypesDto::class, '{"bool":[1]}', 'bool'];
        yield 'null to array' => [NonScalarTypesDto::class, '{"array":null}', 'array'];
        yield 'null to date' => [NonScalarTypesDto::class, '{"date":null}', 'date'];
        yield 'null to uuid' => [NonScalarTypesDto::class, '{"uuid":null}', 'uuid'];
        yield 'null to enum' => [NonScalarTypesDto::class, '{"enum":null}', 'enum'];
        yield 'array to date' => [NonScalarTypesDto::class, '{"date":[1]}', 'date'];
        yield 'array to uuid' => [NonScalarTypesDto::class, '{"uuid":[1]}', 'uuid'];
        yield 'text to uuid' => [NonScalarTypesDto::class, '{"uuid":"abc"}', 'uuid'];
        yield 'array to enum' => [NonScalarTypesDto::class, '{"enum":[1]}', 'enum'];
        yield 'text in a list of int' => [NonScalarTypesDto::class, '{"ids":"1,abc"}', 'ids'];
        yield 'two to bool' => [ScalarTypesDto::class, '{"bool":2}', 'bool'];
        yield 'fraction to bool' => [ScalarTypesDto::class, '{"bool":1.5}', 'bool'];
        yield 'number to a string backed enum' => [NonScalarTypesDto::class, '{"enum":5}', 'enum'];
        yield 'text to an int backed enum' => [NonScalarTypesDto::class, '{"intEnum":"abc"}', 'intEnum'];
        yield 'fraction as text to an int backed enum' => [NonScalarTypesDto::class, '{"intEnum":"1.5"}', 'intEnum'];
    }

    /**
     * @throws SerializerException
     */
    public function testListOfNumbersFromAString(): void
    {
        self::assertSame([1, 2], $this->serializer->deserialize('{"ids":"1,2"}', NonScalarTypesDto::class)->ids);
    }

    /**
     * @throws SerializerException
     */
    #[DataProvider('nullableParameter')]
    public function testNullableParameterTakesAValueAndNull(string $json, ?string $expected): void
    {
        self::assertSame($expected, $this->serializer->deserialize($json, NullableParameterDto::class)->getName());
    }

    public static function nullableParameter(): iterable
    {
        yield 'value' => ['{"name":"anzu"}', 'anzu'];
        yield 'null' => ['{"name":null}', null];
    }

    public function testErrorOfAConstructorParameterNamesTheClass(): void
    {
        $this->expectException(DeserializationException::class);
        $this->expectExceptionMessage(sprintf('Unable to deserialize "%s::$name". Expected string, got array.', NullableParameterDto::class));

        $this->serializer->deserialize('{"name":[1]}', NullableParameterDto::class);
    }

    /**
     * @throws SerializerException
     */
    public function testReadonlyPropertyTakesItsValueThroughANullableParameter(): void
    {
        $dto = $this->serializer->deserialize(
            '{"at":"2026-01-02T03:04:05Z","name":"anzu"}',
            ReadonlyNullableParameterDto::class,
        );

        self::assertEquals(new DateTimeImmutable('2026-01-02T03:04:05Z'), $dto->at);
        self::assertSame('anzu', $dto->name);
    }

    /**
     * @throws SerializerException
     */
    public function testReadonlyPropertyKeepsTheConstructorValueWithoutInput(): void
    {
        $dto = $this->serializer->deserialize('{"name":null}', ReadonlyNullableParameterDto::class);

        self::assertEquals(new DateTimeImmutable('2020-01-01T00:00:00Z'), $dto->at);
        self::assertSame('default', $dto->name);
    }

    /**
     * @throws SerializerException
     */
    public function testSetHookTakesItsWiderType(): void
    {
        self::assertSame(['a', 'b'], $this->serializer->deserialize('{"tags":"a,b"}', WiderSetHookDto::class)->tags);
        self::assertSame(['a'], $this->serializer->deserialize('{"tags":["a"]}', WiderSetHookDto::class)->tags);
        self::assertSame('from null', $this->serializer->deserialize('{"name":null}', WiderSetHookDto::class)->name);
    }

    /**
     * @throws SerializerException
     */
    #[DataProvider('scalarUnion')]
    public function testScalarUnionKeepsTheTypeOfTheInput(string $json, string $property, mixed $expected): void
    {
        self::assertSame($expected, $this->serializer->deserialize($json, ScalarUnionDto::class)->{$property});
    }

    public static function scalarUnion(): iterable
    {
        yield 'int in int or string' => ['{"value":5}', 'value', 5];
        yield 'string in int or string' => ['{"value":"anzu"}', 'value', 'anzu'];
        yield 'float in int or float' => ['{"number":1.5}', 'number', 1.5];
        yield 'int in int or float' => ['{"number":2}', 'number', 2];
        yield 'null in a nullable union' => ['{"number":null}', 'number', null];
    }

    /**
     * @throws SerializerException
     */
    public function testNameFromAMethodDropsOnlyThePrefix(): void
    {
        self::assertSame(
            '{"size":1,"title":"title","issue":2,"count":3,"active":true,"getter":4}',
            $this->serializer->serialize(new MethodNamesDto()),
        );
    }
}
