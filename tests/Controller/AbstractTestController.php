<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Controller;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
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
        $serializer = self::getContainer()->get(Serializer::class);
        if ($serializer instanceof Serializer) {
            $this->serializer = $serializer;
        }
    }

    protected function get(string $uri): string
    {
        self::$client->request(method: Request::METHOD_GET, uri: $uri);

        return (string) self::$client->getResponse()->getContent();
    }

    /**
     * @template T of object
     *
     * @param string $uri
     * @param T $payload
     *
     * @return T
     *
     * @throws SerializerException
     */
    protected function post(string $uri, object $payload): object
    {
        $payloadSerialized = $this->serializer->serialize($payload);
        self::$client->request(method: Request::METHOD_POST, uri: $uri, content: $payloadSerialized);
        $content = (string) self::$client->getResponse()->getContent();

        return $this->serializer->deserialize($content, $payload::class);
    }

    protected static function getKernelClass(): string
    {
        return AnzuTestKernel::class;
    }
}
