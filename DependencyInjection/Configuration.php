<?php

namespace Maci\OrderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('maci_order');

        $rootNode
            ->children()
                ->arrayNode('payments')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function($v) { return array('label' => $v, 'cost' => 0); })
                        ->end()
                        ->children()
                            ->scalarNode('label')->isRequired()->end()
                            ->integerNode('cost')->defaultValue(0)->min(0)->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('couriers')
                    ->prototype('array')
                        ->children()
                            ->integerNode('default_cost')->defaultValue(0)->min(0)->end()
                            ->scalarNode('label')->end()
                            ->scalarNode('note')->end()
                            ->arrayNode('countries')
                                ->prototype('array')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function($v) { return array('cost' => intval($v)); })
                                    ->end()
                                    ->children()
                                        ->integerNode('cost')->min(0)->end()
                                        ->scalarNode('note')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_tax')->defaultValue(22)->end()
                ->scalarNode('free_shipping_over')->defaultValue(150)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
