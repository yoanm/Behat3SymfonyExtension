<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

abstract class AbstractSubExtension implements CompilerPassInterface
{
    /**
     * Returns the extension config key.
     *
     * @return string|false false in case no config
     */
    public function getConfigKey()
    {
        return false;
    }

    /**
     * Setups configuration for the extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder)
    {
    }

    /**
     * Loads extension services into temporary container.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    abstract public function load(ContainerBuilder $container, array $config);

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function getContainerParamOrServiceId($key)
    {
        return sprintf(
            '%s.%s',
            Behat3SymfonyExtension::BASE_CONTAINER_ID,
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
        $argumentList = array(),
        $tagList = array(),
        $addMethodCallList = array()
    ) {
        $definition = new Definition($class, $argumentList);

        foreach ($tagList as $tag) {
            $definition->addTag($tag);
        }

        foreach ($addMethodCallList as $methodCall) {
            $definition->addMethodCall($methodCall[0], $methodCall[1]);
        }

        $container->setDefinition($this->getContainerParamOrServiceId($id), $definition);
    }
}
