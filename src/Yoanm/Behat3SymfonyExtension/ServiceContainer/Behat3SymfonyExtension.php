<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Configuration\KernelConfiguration;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Configuration\LoggerConfiguration;
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

    /**
     * (Will be covered by Functional tests)
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->append((new KernelConfiguration())->getConfigTreeBuilder());
        $builder->append((new LoggerConfiguration())->getConfigTreeBuilder());
    }
    // @codeCoverageIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        foreach ($config['kernel'] as $key => $value) {
            $container->setParameter(sprintf('behat3_symfony_extension.kernel.%s', $key), $value);
        }
        foreach ($config['logger'] as $key => $value) {
            $container->setParameter(sprintf('behat3_symfony_extension.logger.%s', $key), $value);
        }
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('client.xml');
        $loader->load('kernel.xml');
        $loader->load('initializer.xml');
        $loader->load('logger.xml');
        if (true === $config['kernel']['reboot']) {
            $loader->load('kernel_auto_reboot.xml');
        }
        if (true === $config['kernel']['debug']) {
            $loader->load('kernel_debug_mode.xml');
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
}
