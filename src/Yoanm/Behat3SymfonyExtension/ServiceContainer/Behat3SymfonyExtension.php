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
        /**
         * @codeCoverageIgnoreStart
         * Not possible to test this because of ExtensionManager is a final class
         */
        $extensionManager->getExtension('mink')
            ->registerDriverFactory(new Behat3SymfonyDriverFactory());

        $this->subExtensionList[] = new KernelSubExtension();
        $this->subExtensionList[] = new LoggerSubExtension();
        $this->subExtensionList[] = new HandlerSubExtension();
        $this->subExtensionList[] = new InitializerSubExtension();
        $this->subExtensionList[] = new SubscriberSubExtension();
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $node = $builder
            ->addDefaultsIfNotSet()
            ->children();
        foreach ($this->subExtensionList as $subExtension) {
            if (false !== $subExtension->getConfigKey()) {
                $tree = new TreeBuilder();
                $subBuilder = $tree->root($subExtension->getConfigKey());
                $subExtension->configure($subBuilder);
                $node->append($subBuilder);
            }
        }

        $node->end();
        // @codeCoverageIgnoreEnd
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
