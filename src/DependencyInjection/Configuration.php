<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vuillaume_agency_turnstile');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('enable')
            ->defaultTrue()
            ->end()
            ->scalarNode('key')
            ->defaultValue('%env(TURNSTILE_KEY)%')
            ->end()
            ->scalarNode('secret')
            ->defaultValue('%env(TURNSTILE_SECRET)%')
            ->end()
            ->end();

        return $treeBuilder;
    }
}
