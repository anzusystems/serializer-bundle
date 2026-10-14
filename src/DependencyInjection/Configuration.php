<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\DependencyInjection;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'anzu_systems_serializer';
    public const CONFIG_DATE_FORMAT = 'date_format';
    public const CONFIG_PARAMETER_BAG = 'parameter_bag';
    public const CONFIG_METADATA_CACHE_POOL = 'metadata_cache_pool';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder(self::ALIAS);
        $tree->getRootNode()
            ->children()
                ->scalarNode(self::CONFIG_DATE_FORMAT)->defaultValue('Y-m-d\TH:i:s.u\Z')->end()
                ->arrayNode(self::CONFIG_PARAMETER_BAG)->scalarPrototype()->end()->end()
                ->scalarNode(self::CONFIG_METADATA_CACHE_POOL)
                    ->info('Service id of the PSR-6 pool that keeps the class metadata.')
                    ->defaultValue(CacheItemPoolInterface::class)
                ->end()
            ->end()
        ;

        return $tree;
    }
}
