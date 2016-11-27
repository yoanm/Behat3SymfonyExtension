<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\BehatContextSubscriberInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelHandlerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\LoggerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;
use Yoanm\Behat3SymfonyExtension\Logger\SfKernelEventLogger;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Driver\Behat3SymfonyDriverFactory;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;
use Yoanm\Behat3SymfonyExtension\Subscriber\SfKernelLoggerSubscriber;

class Behat3SymfonyExtension implements Extension
{
    const BASE_CONTAINER_ID = 'behat3_symfony_extension';
    const KERNEL_SERVICE_ID = 'behat3_symfony_extension.kernel';

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
        $this->loadKernel($container, $config['kernel']);
        $this->loadLogger($container, $config['logger']);
        $this->loadHandler($container);
        $this->loadInitializer($container);
        $this->loadSubscriber($container, $config['kernel']);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $bootstrapPath = $container->getParameter($this->getContainerParamOrServiceId('kernel.bootstrap'));
        if ($bootstrapPath) {
            $bootstrap = sprintf(
                '%s/%s',
                $container->getParameter('paths.base'),
                $bootstrapPath
            );
            if (file_exists($bootstrap)) {
                require_once($bootstrap);
            } else {
                throw new ProcessingException('Could not find bootstrap file !');
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $kernelConfig
     */
    protected function loadKernel(ContainerBuilder $container, array $kernelConfig)
    {
        $container->setParameter(
            $this->getContainerParamOrServiceId('kernel.reboot'),
            $kernelConfig['reboot']
        );
        $this->createService(
            $container,
            'kernel',
            $kernelConfig['class'],
            array(
                $kernelConfig['env'],
                $kernelConfig['debug'],
            )
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $loggerConfig
     */
    protected function loadLogger(ContainerBuilder $container, array $loggerConfig)
    {
        $baseHandlerServiceId = 'logger.handler';
        // Handler
        $this->createService(
            $container,
            $baseHandlerServiceId,
            StreamHandler::class,
            array(
                sprintf(
                    '%s/%s',
                    '%behat.paths.base%',
                    sprintf(
                        '%%%s%%',
                        $loggerConfig['path']
                    )
                ),
                $loggerConfig['level'],
            )
        );
        // Logger
        $this->createService(
            $container,
            'logger',
            Logger::class,
            array(
                'behat3Symfony',
                $loggerConfig['level'],
            ),
            array('event_dispatcher.subscriber'),
            array(
                array(
                    'pushHandler',
                    array(new Reference($this->getContainerParamOrServiceId($baseHandlerServiceId)))
                )
            )
        );
        // SfKernelEventLogger
        $this->createService(
            $container,
            'logger.sf_kernel_logger',
            SfKernelEventLogger::class,
            array(
                new Reference($this->getContainerParamOrServiceId('kernel'))
            ),
            array('event_dispatcher.subscriber')
        );
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadHandler(ContainerBuilder $container)
    {
        $this->createService(
            $container,
            'handler.kernel',
            KernelHandler::class,
            array(
                new Reference('event_dispatcher'),
                new Reference(self::KERNEL_SERVICE_ID),
            )
        );
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadInitializer(ContainerBuilder $container)
    {
        // KernelAware
        $this->createService(
            $container,
            'initializer.kernel_aware',
            KernelHandlerAwareInitializer::class,
            array(
                new Reference($this->getContainerParamOrServiceId('handler.kernel')),
            ),
            array('context.initializer')
        );
        // LoggerAware
        $this->createService(
            $container,
            'initializer.logger_aware',
            LoggerAwareInitializer::class,
            array(
                new Reference($this->getContainerParamOrServiceId('logger')),
            ),
            array('context.initializer')
        );
        // BehatSubscriber
        $this->createService(
            $container,
            'initializer.behat_subscriber',
            BehatContextSubscriberInitializer::class,
            array(
                new Reference('event_dispatcher'),
            ),
            array('context.initializer')
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $kernelConfig
     */
    protected function loadSubscriber(ContainerBuilder $container, array $kernelConfig)
    {
        $this->createService(
            $container,
            'subscriber.sf_kernel_logger',
            SfKernelLoggerSubscriber::class,
            array(
                new Reference($this->getContainerParamOrServiceId('logger.sf_kernel_logger')),
            ),
            array('event_dispatcher.subscriber')
        );

        if (true === $kernelConfig['reboot']) {
            $this->loadAutoRebootKernel($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadAutoRebootKernel(ContainerBuilder $container)
    {
        $this->createService(
            $container,
            'subscriber.reboot_kernel',
            RebootKernelSubscriber::class,
            array(
                new Reference($this->getContainerParamOrServiceId('handler.kernel')),
            ),
            array(
                'event_dispatcher.subscriber'
            )
        );
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getContainerParamOrServiceId($key)
    {
        return sprintf(
            '%s.%s',
            self::BASE_CONTAINER_ID,
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
    private function createService(
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
