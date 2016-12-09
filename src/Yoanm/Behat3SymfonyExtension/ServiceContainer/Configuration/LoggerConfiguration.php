<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\Configuration;

use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class LoggerConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('kernel');
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
                    ->defaultValue(Logger::DEBUG)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
