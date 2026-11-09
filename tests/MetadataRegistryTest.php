<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use AnzuSystems\SerializerBundle\Tests\TestApp\Proxy\ExampleProxy;

final class MetadataRegistryTest extends AbstractTestCase
{
    public function testGeneratedProxyResolvesToEntityMetadata(): void
    {
        $metadataRegistry = self::getContainer()->get(MetadataRegistry::class);

        self::assertSame($metadataRegistry->get(Example::class), $metadataRegistry->get(ExampleProxy::class));
    }
}
