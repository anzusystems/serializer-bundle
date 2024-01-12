<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Exception\DeserializationException;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;

final class SerializerValidationExceptionTest extends AbstractTestCase
{
    /**
     * @throws SerializerException
     */
    public function testInvalidJsonString(): void
    {
        $this->expectException(DeserializationException::class);
        $this->expectExceptionMessage('Cannot decode JSON string provided.');
        $this->serializer->deserialize('{id":1}', Example::class);
    }

    /**
     * @throws SerializerException
     */
    public function testInvalidDateFormat(): void
    {
        $this->expectException(DeserializationException::class);
        $this->expectExceptionMessage('Unable to create DateTime from format "Y-m-d\TH:i:s\Z" with value "31.12.2023".');
        $this->serializer->deserialize('{"createdAt":"31.12.2023"}', Example::class);
    }

    /**
     * @throws SerializerException
     */
    public function testInvalidBackedEnum(): void
    {
        $this->expectException(DeserializationException::class);
        $this->expectExceptionMessage('Cannot deserialize value "nonexistent" into a BackedEnum. Possible options are: "first", "second", "third".');
        $this->serializer->deserialize('{"place":"nonexistent"}', Example::class);
    }

    /**
     * @throws SerializerException
     */
    public function testInvalidUnitEnum(): void
    {
        $this->expectException(DeserializationException::class);
        $this->expectExceptionMessage('Cannot deserialize value "black" into a UnitEnum. Possible options are: "Red", "Blue", "Green".');
        $this->serializer->deserialize('{"color":"black"}', Example::class);
    }
}
