<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\Configuration;

use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class LoggerConfiguration
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('logger');
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('path')
                    ->defaultValue('var/log/behat.log')
                ->end()
                ->scalarNode('level')
                    ->beforeNormalization()
                    ->always()
                    ->then(function ($value) {
                        return Logger::toMonologLevel($value);
                    })
                    ->end()
                    ->defaultValue(Logger::INFO)
                ->end()
            ->end();

        return $rootNode;
    }
}
