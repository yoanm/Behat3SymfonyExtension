<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

abstract class AbstractExtension implements Extension
{
    const BASE_CONTAINER_ID = 'behat3_symfony_extension';
    const KERNEL_SERVICE_ID = 'behat3_symfony_extension.kernel';

    /**
     * @param string $key
     *
     * @return string
     */
    protected function buildContainerId($key)
    {
        return sprintf(
            '%s.%s',
            self::BASE_CONTAINER_ID,
            $key
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $id
     * @param string           $class
     * @param array            $argumentList
     * @param array            $tagList
     * @param array            $addMethodCallList
     */
    protected function createService(
        ContainerBuilder $container,
        $id,
        $class,
        $argumentList = [],
        $tagList = [],
        $addMethodCallList = []
    ) {
        $definition = new Definition($class, $argumentList);

        foreach ($tagList as $tag) {
            $definition->addTag($tag);
        }

        foreach ($addMethodCallList as $methodCall) {
            $definition->addMethodCall($methodCall[0], $methodCall[1]);
        }

        $container->setDefinition($this->buildContainerId($id), $definition);
    }
}
