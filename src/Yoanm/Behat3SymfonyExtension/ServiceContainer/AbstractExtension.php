<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

abstract class AbstractExtension implements Extension
{
    const BASE_CONTAINER_ID = 'behat3_symfony_extension';
    const KERNEL_SERVICE_ID = 'behat3_symfony_extension.kernel';
    const TEST_CLIENT_SERVICE_ID = 'behat3_symfony_extension.test.client';

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
     * @param string $key
     *
     * @return string
     */
    protected function buildContainerParameterReference($key)
    {
        return sprintf(
            '%%%s%%',
            $this->buildContainerId($key)
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $id
     * @param string           $class
     * @param array            $argumentList
     * @param array            $tagList
     * @param array            $addMethodCallList
     * @param array|null       $factory
     *
     * @return Definition
     */
    protected function createService(
        ContainerBuilder $container,
        $id,
        $class,
        $argumentList = [],
        $tagList = [],
        $addMethodCallList = [],
        $factory = null
    ) {
        $definition = new Definition($class, $argumentList);

        foreach ($tagList as $tag) {
            $definition->addTag($tag);
        }

        foreach ($addMethodCallList as $methodCall) {
            $args = isset($methodCall[1]) ? $methodCall[1] : [];
            $definition->addMethodCall($methodCall[0], $args);
        }

        if (null !== $factory) {
            $definition->setFactory($factory);
        }

        $container->setDefinition($this->buildContainerId($id), $definition);

        return $definition;
    }
}
