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

        $payloadExampleItem1 = (new ExampleItem())
            ->setExample($createdExample)
            ->setName('Ava')
        ;
        $createdExampleItem1 = $this->post('/example/item', $payloadExampleItem1);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals($payloadExampleItem1->getName(), $createdExampleItem1->getName());

        $payloadExampleItem2 = (new ExampleItem())
            ->setExample($createdExample)
            ->setName('Neo')
        ;
        $this->post('/example/item', $payloadExampleItem2);

        $payloadExampleItem3 = (new ExampleItem())
            ->setExample($createdExample)
            ->setName('Bob')
        ;
        $this->post('/example/item', $payloadExampleItem3);

        $responseExample = $this->getDeserialized('/example/' . $createdExample->getId(), Example::class);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertCount(3, $responseExample->getItems());
        self::assertEquals($createdExample->getId(), $responseExample->getId());
        self::assertEquals($payloadExample->getName(), $responseExample->getName());
        self::assertTrue($payloadExample->getUuid()->equals($responseExample->getUuid()));

        self::assertEquals($payloadExampleItem1->getName(), $responseExample->getItems()->first()->getName());
        self::assertEquals($payloadExampleItem2->getName(), $responseExample->getItems()->last()->getName());
    }
}
