<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Controller;

use AnzuSystems\SerializerBundle\Serializer;
use AnzuSystems\SerializerBundle\Tests\AnzuTestKernel;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractTestController extends WebTestCase
{
    protected static KernelBrowser $client;
    protected Serializer $serializer;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        static::$client = static::createClient();
        static::$client->disableReboot();
        $this->serializer = self::getContainer()->get(Serializer::class);
    }

    protected function get(string $uri): string
    {
        self::$client->request(method: Request::METHOD_GET, uri: $uri);

        return (string) self::$client->getResponse()->getContent();
    }

    protected static function getKernelClass(): string
    {
        return AnzuTestKernel::class;
    }
}
