<?php
declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Serializer;
use Exception;
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
        $this->serializer = self::getContainer()->get(Serializer::class);
    }

    protected static function getKernelClass(): string
    {
        return AnzuTestKernel::class;
    }
}
