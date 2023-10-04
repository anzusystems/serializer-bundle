<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AnzuAppTest extends KernelTestCase
{
    public function testTes(): void
    {
        self::assertEquals('test', 'test');
    }
}
