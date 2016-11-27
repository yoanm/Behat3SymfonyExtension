<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Driver\Behat3SymfonyDriverFactory;

class Behat3SymfonyExtension implements Extension
{
    const BASE_CONTAINER_PARAMETER = 'behat3_symfony_extension';

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'behat3_symfony';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        $extensionManager->getExtension('mink')
            ->registerDriverFactory(new Behat3SymfonyDriverFactory());
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $castToBool = function ($value) {
            $filtered = filter_var(
                $value,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            return (null === $filtered) ? (bool) $value : $filtered;
        };

        $builder
            ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('logger')
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
                        ->end()
                    ->end()
                    ->arrayNode('kernel')
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
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        // load config files
        $loader->load('kernel.xml');
        $loader->load('logger.xml');
        $loader->load('initializer.xml');
        $loader->load('handler.xml');
        $loader->load('subscriber.xml');

        if (true === $config['kernel']['reboot']) {
            $loader->load('auto_reboot_kernel.xml');
        }

        foreach ($config['kernel'] as $configKey => $configValue) {
            $container->setParameter(
                sprintf(
                    '%s.kernel.%s',
                    self::BASE_CONTAINER_PARAMETER,
                    $configKey
                ),
                $configValue
            );
        }
        foreach ($config['logger'] as $configKey => $configValue) {
            $container->setParameter(
                sprintf(
                    '%s.logger.%s',
                    self::BASE_CONTAINER_PARAMETER,
                    $configKey
                ),
                $configValue
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $bootstrapPath = $container->getParameter('behat3_symfony_extension.kernel.bootstrap');
        if ($bootstrapPath) {
            $bootstrap = sprintf(
                '%s%s%s',
                $container->getParameter('paths.base'),
                PATH_SEPARATOR,
                $bootstrapPath
            );
            if (file_exists($bootstrap)) {
                require_once($bootstrap);
            } else {
                throw new ProcessingException('Could not find bootstrap file !');
            }
        }
    }
}
