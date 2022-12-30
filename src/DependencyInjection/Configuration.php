<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'anzu_systems_serializer';
    public const CONFIG_DATE_FORMAT = 'date_format';
    public const CONFIG_PARAMETER_BAG = 'parameter_bag';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder(self::ALIAS);
        $tree->getRootNode()
            ->children()
                ->scalarNode(self::CONFIG_DATE_FORMAT)->defaultValue('Y-m-d\TH:i:s.u\Z')->end()
                ->arrayNode(self::CONFIG_PARAMETER_BAG)->scalarPrototype()->end()
            ->end()
        ;

        return $tree;
    }
}
