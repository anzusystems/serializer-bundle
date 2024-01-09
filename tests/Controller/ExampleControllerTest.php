<?php
declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Controller;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ExampleControllerTest extends AbstractTestController
{
    /**
     * @throws SerializerException
     */
    public function testCrud(): void
    {
        $payload = (new Example())
            ->setName('Created example.')
            ->setUuid(Uuid::v4())
        ;
        $created = $this->post('/example', $payload);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals($payload->getName(), $created->getName());
        self::assertTrue($payload->getUuid()->equals($created->getUuid()));

        $response = $this->getDeserialized('/example/' . $created->getId(), Example::class);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertEquals($payload->getName(), $response->getName());
        self::assertTrue($payload->getUuid()->equals($response->getUuid()));
    }
}
