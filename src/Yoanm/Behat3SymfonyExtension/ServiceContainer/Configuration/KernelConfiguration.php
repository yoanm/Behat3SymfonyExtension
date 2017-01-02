<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Yoanm\BehatUtilsExtension\ServiceContainer\Configuration\ConfigurationInterface;

class KernelConfiguration implements ConfigurationInterface
{
    public function getConfigNode()
    {
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
                    ->defaultTrue()
                ->end()
                ->booleanNode('reboot')
                    ->info('If true symfony kernel will be rebooted after each scenario/example')
                    ->defaultTrue()
                ->end()
            ->end();

        return $rootNode;
    }
}
