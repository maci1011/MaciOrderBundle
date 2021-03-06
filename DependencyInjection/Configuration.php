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
        $treeBuilder = new TreeBuilder('maci_order');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('payments')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function($v) { return array('gateway' => $v, 'cost' => 0); })
                        ->end()
                        ->children()
                            ->scalarNode('gateway')->isRequired()->end()
                            ->scalarNode('label')->end()
                            ->integerNode('cost')->defaultValue(0)->min(0)->end()
                            ->booleanNode('sandbox')->defaultValue(false)->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('couriers')
                    ->prototype('array')
                        ->children()
                            ->integerNode('default_cost')->defaultValue(0)->min(0)->end()
                            ->scalarNode('label')->end()
                            ->scalarNode('note')->end()
                            ->arrayNode('payments')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function($v) { return array($v); })
                                ->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('countries')
                                ->prototype('array')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function($v) { return array('cost' => floatval($v)); })
                                    ->end()
                                    ->children()
                                        ->floatNode('cost')->min(0)->end()
                                        ->scalarNode('note')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_tax')->defaultValue(22)->end()
                ->scalarNode('free_shipping_over')->defaultValue(0)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
