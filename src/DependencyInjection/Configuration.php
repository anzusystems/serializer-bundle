<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'anzu_systems_serializer';
    public const CONFIG_DATE_FORMAT = 'date_format';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder(self::ALIAS);
        $tree->getRootNode()
            ->children()
                ->scalarNode(self::CONFIG_DATE_FORMAT)->defaultValue('Y-m-d\TH:i:s.u\Z')->end()
            ->end()
        ;

        return $tree;
    }
}
