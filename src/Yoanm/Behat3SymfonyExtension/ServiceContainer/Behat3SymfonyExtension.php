<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Configuration\KernelConfiguration;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\DriverFactory\Behat3SymfonyFactory;

class Behat3SymfonyExtension implements Extension
{
    const TEST_CLIENT_SERVICE_ID = 'behat3_symfony_extension.test.client';
    const KERNEL_SERVICE_ID = 'behat3_symfony_extension.kernel';

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'behat3_symfony';
    }

    // @codeCoverageIgnoreStart
    /**
     * (Not possible to cover this because ExtensionManager is a final class)
     *
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        $minExtension = $extensionManager->getExtension('mink');
        if ($minExtension instanceof MinkExtension) {
            $minExtension->registerDriverFactory(new Behat3SymfonyFactory());
        }
    }
    // @codeCoverageIgnoreEnd

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
        $builder->children()
            ->booleanNode('debug_mode')
                ->beforeNormalization()
                ->always()
                    ->then($castToBool)
                ->end()
                ->defaultFalse()
            ->end()
            ->end();
        $builder->append((new KernelConfiguration())->getConfigNode());
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $config = $this->normalizeConfig($config);
        $this->bindConfigToContainer($container, $config);

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('client.xml');
        $loader->load('kernel.xml');
        $loader->load('initializer.xml');
        if (true === $config['kernel']['reboot']) {
            $loader->load('kernel_auto_reboot.xml');
        }
        if (true === $config['kernel']['debug']) {
            $loader->load('kernel_debug_mode.xml');

            // Override log level parameter
            $this->checkUtilsExtensionAlreadyLoaded($container);
            $container->setParameter('behat_utils_extension.logger.level', Logger::DEBUG);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $bootstrapPath = $container->getParameter('behat3_symfony_extension.kernel.bootstrap');
        if ($bootstrapPath) {
            require_once($this->normalizePath($container, $bootstrapPath));
        }

        // load kernel
        $container->getDefinition(self::KERNEL_SERVICE_ID)
            ->setFile(
                $this->normalizePath(
                    $container,
                    $container->getParameter('behat3_symfony_extension.kernel.path')
                )
            );
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $path
     *
     * @return string
     */
    protected function normalizePath(ContainerBuilder $container, $path)
    {
        $basePath = $container->getParameter('paths.base');
        $pathUnderBasePath = sprintf('%s/%s', $basePath, $path);
        if (file_exists($pathUnderBasePath)) {
            $path = $pathUnderBasePath;
        }

        return $path;
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     * @param string           $baseId
     */
    protected function bindConfigToContainer(
        ContainerBuilder $container,
        array $config,
        $baseId = 'behat3_symfony_extension'
    ) {
        foreach ($config as $configKey => $configValue) {
            if (is_array($configValue)) {
                $this->bindConfigToContainer(
                    $container,
                    $configValue,
                    sprintf('%s.%s', $baseId, $configKey)
                );
            } else {
                $container->setParameter(sprintf('%s.%s', $baseId, $configKey), $configValue);
            }
        }
    }

    /**
     * @param array $config
     * @return array
     */
    protected function normalizeConfig(array $config)
    {
        if (true === $config['debug_mode']) {
            $config['kernel']['debug'] = true;
        }

        return $config;
    }

    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    protected function checkUtilsExtensionAlreadyLoaded(ContainerBuilder $container)
    {
        if (!$container->hasParameter('behat_utils_extension.logger.path')) {
            throw new \Exception('BehatUtilsExtension must be loaded before this one !');
        }
    }
}
