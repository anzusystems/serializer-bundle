<?php
declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Controller;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use Symfony\Component\HttpFoundation\Response;

final class DummyControllerTest extends AbstractTestController
{
    public function testOk(): void
    {
        $this->get('/dummy/ok');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @throws SerializerException
     */
    public function testValueResolver(): void
    {
        $payload = (new Example())->setName('Some example name.');
        $response1 = $this->post('/dummy/value-resolver', $payload);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertEquals($payload->getName(), $response1->getName());

        $response2 = $this->getDeserialized(
            '/dummy/value-resolver',
            Example::class,
            ['name' => $payload->getName()],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertEquals($payload->getName(), $response2->getName());
    }
}
