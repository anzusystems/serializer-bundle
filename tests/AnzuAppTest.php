<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use DateTimeImmutable;

final class AnzuAppTest extends AbstractTestCase
{
    /**
     * @throws SerializerException
     */
    public function testTes(): void
    {
        $deserialized = (new Example())
            ->setId(1)
            ->setName('Test name')
            ->setCreatedAt(new DateTimeImmutable('2023-12-31T00:00:00Z'))
        ;
        $serialized = '{"id":1,"name":"Test name","createdAt":"2023-12-31T00:00:00Z"}';
        $actual = $this->serializer->serialize($deserialized);
        self::assertEquals($serialized, $actual);
    }
}
