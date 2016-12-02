<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\BehatContextSubscriberInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelHandlerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\LoggerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\KernelSubExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\LoggerSubExtension;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;
use Yoanm\Behat3SymfonyExtension\Subscriber\SfKernelLoggerSubscriber;

class Behat3SymfonyExtension extends AbstractExtension
{
    /** @var Extension[] */
    private $subExtensionList = array();

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
        foreach ($this->subExtensionList as $subExtension) {
            $subExtension->load($container, $config);
        }
        $this->createService(
            $container,
            'handler.kernel',
            KernelHandler::class,
            [
                new Reference('event_dispatcher'),
                new Reference(self::KERNEL_SERVICE_ID),
            ]
        );
        $this->createService(
            $container,
            'initializer.kernel_aware',
            KernelHandlerAwareInitializer::class,
            [new Reference($this->buildContainerId('handler.kernel'))],
            ['context.initializer']
        );
        $this->createService(
            $container,
            'initializer.logger_aware',
            LoggerAwareInitializer::class,
            [new Reference($this->buildContainerId('logger'))],
            ['context.initializer']
        );
        $this->createService(
            $container,
            'initializer.behat_subscriber',
            BehatContextSubscriberInitializer::class,
            [new Reference('event_dispatcher')],
            ['context.initializer']
        );
        $this->createService(
            $container,
            'subscriber.sf_kernel_logger',
            SfKernelLoggerSubscriber::class,
            [new Reference($this->buildContainerId('logger.sf_kernel_logger'))],
            ['event_dispatcher.subscriber']
        );

        if (true === $config['kernel']['reboot']) {
            $this->createService(
                $container,
                'subscriber.reboot_kernel',
                RebootKernelSubscriber::class,
                [new Reference($this->buildContainerId('handler.kernel'))],
                ['event_dispatcher.subscriber']
            );
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
