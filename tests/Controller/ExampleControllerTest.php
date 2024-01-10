<?php
declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Controller;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\ExampleItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ExampleControllerTest extends AbstractTestController
{
    /**
     * @throws SerializerException
     */
    public function testCrud(): void
    {
        $payloadExample = (new Example())
            ->setName('Created example.')
            ->setUuid(Uuid::v4())
        ;
        $createdExample = $this->post('/example', $payloadExample);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals($payloadExample->getName(), $createdExample->getName());
        self::assertTrue($payloadExample->getUuid()->equals($createdExample->getUuid()));

        $payloadExampleItem = (new ExampleItem())
            ->setExample($createdExample)
            ->setName('Created example item.')
        ;
        $createdExampleItem = $this->post('/example/item', $payloadExampleItem);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals($payloadExampleItem->getName(), $createdExampleItem->getName());

        $responseExample = $this->getDeserialized('/example/' . $createdExample->getId(), Example::class);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertEquals($createdExample->getId(), $responseExample->getId());
        self::assertEquals($payloadExample->getName(), $responseExample->getName());
        self::assertTrue($payloadExample->getUuid()->equals($responseExample->getUuid()));
        self::assertEquals($payloadExampleItem->getExample()->getId(), $responseExample->getItems()->first()->getExample()->getId());
    }
}
