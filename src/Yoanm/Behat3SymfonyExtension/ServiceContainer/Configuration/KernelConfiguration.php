<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class KernelConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $castToBool = function ($value) {
            $filtered = filter_var(
                $value,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            return (null === $filtered) ? (bool) $value : $filtered;
        };
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('kernel');
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('bootstrap')
                    ->defaultValue('app/autoload.php')
                ->end()
                ->scalarNode('path')
                    ->defaultValue('app/AppKernel.php')
                ->end()
                ->scalarNode('class')
                    ->defaultValue('AppKernel')
                ->end()
                ->scalarNode('env')
                    ->defaultValue('test')
                ->end()
                ->booleanNode('debug')
                    ->beforeNormalization()
                    ->always()
                        ->then($castToBool)
                    ->end()
                    ->defaultTrue()
                ->end()
                ->booleanNode('reboot')
                    ->info('If true symfony kernel will be rebooted after each scenario/example')
                    ->beforeNormalization()
                        ->always()
                        ->then($castToBool)
                    ->end()
                    ->defaultTrue()
                ->end()
            ->end();

        return $rootNode;
    }
}
