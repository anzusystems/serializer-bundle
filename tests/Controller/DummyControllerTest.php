<?php
declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

final class DummyControllerTest extends AbstractTestController
{
    public function testDummy(): void
    {
        $this->get('/dummy/ok');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}