<?php

namespace Wizards\RestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('wizards_rest');

        $rootNode
            ->children()
                ->enumNode('data_source')->values(['orm', 'array'])->defaultValue('array')->end()
                ->enumNode('reader')->values(['annotation', 'array'])->defaultValue('array')->end()
                ->scalarNode('base_url')->defaultValue('http://test.com')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
