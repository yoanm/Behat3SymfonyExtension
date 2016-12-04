<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Client\Client;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelHandlerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Dispatcher\BehatKernelEventDispatcher;
use Yoanm\Behat3SymfonyExtension\Factory\KernelFactory;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\AbstractExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\DriverFactory\Behat3SymfonyFactory;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;

class KernelSubExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getConfigKey()
    {
        return 'kernel';
    }

    // @codeCoverageIgnoreStart
    // Not possible to cover this because ExtensionManager is a final class
    /**
     * @inheritDoc
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        $minExtension = $extensionManager->getExtension('mink');
        if ($minExtension instanceof MinkExtension) {
            $minExtension->registerDriverFactory(new Behat3SymfonyFactory());
        }
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    // Will be covered by FT
    /**
     * @inheritDoc
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
            ->end();
    }
    // @codeCoverageIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $kernelConfig = $config[$this->getConfigKey()];

        $this->loadContainerParameter($container, $kernelConfig);
        $this->loadInitializer($container);
        $this->loadSubscriber($container, $kernelConfig);
        $this->createService(
            $container,
            'test.client',
            Client::class,
            [
                    new Reference(self::KERNEL_SERVICE_ID),
                    [],
                    new Reference($this->buildContainerId('test.client.history')),
                    new Reference($this->buildContainerId('test.client.cookiejar'))
                ]
        );
        $this->createService(
            $container,
            'test.client.history',
            History::class
        );
        $this->createService(
            $container,
            'test.client.cookiejar',
            CookieJar::class
        );
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
            'dispatcher.kernel_event',
            BehatKernelEventDispatcher::class,
            [new Reference('event_dispatcher')]
        );
        // Load Kernel thanks to the factory
        $this
            ->createService(
                $container,
                'kernel',
                $kernelConfig['class']
            )
            ->setFactory([new Reference($this->buildContainerId('factory.kernel')), 'load']);

        $this->createService(
            $container,
            'factory.kernel',
            KernelFactory::class,
            [
                new Reference($this->buildContainerId('dispatcher.kernel_event')),
                '%'.$this->buildContainerId('kernel.path').'%',
                '%'.$this->buildContainerId('kernel.class').'%',
                '%'.$this->buildContainerId('kernel.env').'%',
                '%'.$this->buildContainerId('kernel.debug').'%'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $basePath = $container->getParameter('paths.base');
        $bootstrapPath = $container->getParameter($this->buildContainerId('kernel.bootstrap'));
        if ($bootstrapPath) {
            $bootstrapPathUnderBasePath = sprintf('%s/%s', $basePath, $bootstrapPath);
            if (file_exists($bootstrapPathUnderBasePath)) {
                $bootstrapPath = $bootstrapPathUnderBasePath;
            }
            if (file_exists($bootstrapPath)) {
                require_once($bootstrapPath);
            } else {
                throw new ProcessingException('Could not find bootstrap file !');
            }
        }

        // load kernel
        $kernelPath = $container->getParameter($this->buildContainerId('kernel.path'));
        $kernelPathUnderBasePath = sprintf('%s/%s', $basePath, $kernelPath);
        if (file_exists($kernelPathUnderBasePath)) {
            $kernelPath = $kernelPathUnderBasePath;
        }

        $container->getDefinition(self::KERNEL_SERVICE_ID)
            ->setFile($kernelPath);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadInitializer(ContainerBuilder $container)
    {
        $this->createService(
            $container,
            'initializer.kernel_aware',
            KernelAwareInitializer::class,
            [new Reference(self::KERNEL_SERVICE_ID)],
            ['context.initializer']
        );

        $this->createService(
            $container,
            'initializer.kernel_handler_aware',
            KernelHandlerAwareInitializer::class,
            [new Reference($this->buildContainerId('handler.kernel'))],
            ['context.initializer']
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param $kernelConfig
     */
    protected function loadSubscriber(ContainerBuilder $container, $kernelConfig)
    {
        if (true === $kernelConfig['reboot']) {
            $this->createService(
                $container,
                'subscriber.reboot_kernel',
                RebootKernelSubscriber::class,
                [new Reference($this->buildContainerId('handler.kernel'))],
                [EventDispatcherExtension::SUBSCRIBER_TAG]
            );
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param $kernelConfig
     */
    protected function loadContainerParameter(ContainerBuilder $container, $kernelConfig)
    {
        foreach ($kernelConfig as $key => $value) {
            $container->setParameter($this->buildContainerId(sprintf('kernel.%s', $key)), $value);
        }
    }
}
