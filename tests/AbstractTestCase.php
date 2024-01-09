<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Serializer;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractTestCase extends KernelTestCase
{
    protected Serializer $serializer;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $serializer = self::getContainer()->get(Serializer::class);
        if (false === ($serializer instanceof Serializer)) {
            throw new RuntimeException('Cannot get Serializer from container.');
        }
        $this->serializer = $serializer;
    }

    protected static function getKernelClass(): string
    {
        return AnzuTestKernel::class;
    }
}
