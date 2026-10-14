<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\DependencyInjection\AnzuSystemsSerializerExtension;
use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AnzuSystemsSerializerExtensionTest extends TestCase
{
    /**
     * @param array<string, string> $config
     */
    #[DataProvider('metadataCachePoolDataProvider')]
    public function testMetadataRegistryUsesTheConfiguredPool(array $config, string $expectedPoolId): void
    {
        $container = new ContainerBuilder();
        (new AnzuSystemsSerializerExtension())->load([$config], $container);

        self::assertSame(
            $expectedPoolId,
            (string) $container->getDefinition(MetadataRegistry::class)->getArgument('$appCache'),
        );
    }

    public static function metadataCachePoolDataProvider(): iterable
    {
        yield 'default' => [[], CacheItemPoolInterface::class];
        yield 'configured' => [['metadata_cache_pool' => 'cache.system'], 'cache.system'];
    }
}
