<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Driver\Behat3SymfonyDriverFactory;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\AbstractSubExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\HandlerSubExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\InitializerSubExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\KernelSubExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\LoggerSubExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\SubscriberSubExtension;

class Behat3SymfonyExtension implements Extension
{
    const BASE_CONTAINER_ID = 'behat3_symfony_extension';
    const KERNEL_SERVICE_ID = 'behat3_symfony_extension.kernel';

    /** @var AbstractSubExtension[] */
    private $subExtensionList = array();

    public function __construct()
    {
        $this->subExtensionList[] = new KernelSubExtension();
        $this->subExtensionList[] = new LoggerSubExtension();
        $this->subExtensionList[] = new HandlerSubExtension();
        $this->subExtensionList[] = new InitializerSubExtension();
        $this->subExtensionList[] = new SubscriberSubExtension();
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
    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        $extensionManager->getExtension('mink')
            ->registerDriverFactory(new Behat3SymfonyDriverFactory());
    }
    // @codeCoverageIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $node = $builder
            ->addDefaultsIfNotSet()
            ->children();
        foreach ($this->subExtensionList as $subExtension) {
            $configKey = $subExtension->getConfigKey();
            if (is_string($configKey)) {
                $tree = new TreeBuilder();
                $subBuilder = $tree->root($configKey);
                $subExtension->configure($subBuilder);
                $node->append($subBuilder);
            }
        }

        $node->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        foreach ($this->subExtensionList as $subExtension) {
            $subExtension->load($container, $config);
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
