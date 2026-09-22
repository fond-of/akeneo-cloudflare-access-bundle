<?php

namespace FondOfAkeneo\Bundle\CloudflareAccessBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('fond_of_akeneo_cloudflare_access');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                // Zero Trust "team domain" - the <name> in https://<name>.cloudflareaccess.com
                ->scalarNode('team_domain')->isRequired()->cannotBeEmpty()->end()
                // Application Audience (AUD) Tag, from the Access application's overview page
                ->scalarNode('application_audience')->isRequired()->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder;
    }
}
