<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\KernelSubExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\LoggerSubExtension;

class Behat3SymfonyExtension implements Extension
{
    const TEST_CLIENT_SERVICE_ID = 'behat3_symfony_extension.test.client';

    /** @var Extension[] */
    private $subExtensionList = [];

    public function __construct(Extension $kernelSubExtension = null, Extension $loggerSubExtension = null)
    {
        $this->subExtensionList[] = $kernelSubExtension ?: new KernelSubExtension();
        $this->subExtensionList[] = $loggerSubExtension ?: new LoggerSubExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'behat3_symfony';
    }

    // @codeCoverageIgnoreStart
    // Not possible to cover this because ExtensionManager is a final class
    // Will be covered by FT
    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        foreach ($this->subExtensionList as $subExtension) {
            $subExtension->initialize($extensionManager);
        }
    }
    // @codeCoverageIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        foreach ($this->subExtensionList as $subExtension) {
            $subExtension->configure(
                $builder->children()
                    ->arrayNode($subExtension->getConfigKey())
            );
        }
    }

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
        foreach ($this->subExtensionList as $subExtension) {
            $subExtension->process($container);
        }
    }
}
