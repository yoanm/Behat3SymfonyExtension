<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\LoggerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Logger\SfKernelEventLogger;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\AbstractExtension;
use Yoanm\Behat3SymfonyExtension\Subscriber\SfKernelLoggerSubscriber;

class LoggerSubExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getConfigKey()
    {
        return 'logger';
    }

    // @codeCoverageIgnoreStart
    // Not possible to cover this because ExtensionManager is a final class
    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    // Will be covered by FT
    /**
     * @inheritDoc
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
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
        ->end();
    }
    // @codeCoverageIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $loggerConfig = $config[$this->getConfigKey()];
        foreach ($loggerConfig as $key => $value) {
            $container->setParameter($this->buildContainerId(sprintf('logger.%s', $key)), $value);
        }
        $baseHandlerServiceId = 'logger.handler';
        $logFilePath = $loggerConfig['path'];
        $logFilePathUnderBasePath = sprintf(
            '%s/%s',
            '%paths.base%',
            $loggerConfig['path']
        );
        if (file_exists($logFilePathUnderBasePath)) {
            $logFilePath = $logFilePathUnderBasePath;
        }
        // Handler
        $this->createService(
            $container,
            $baseHandlerServiceId,
            StreamHandler::class,
            [
                $logFilePath,
                $loggerConfig['level'],
            ]
        );
        // Logger
        $this->createService(
            $container,
            'logger',
            Logger::class,
            ['behat3Symfony'],
            [],
            [
                [
                    'pushHandler',
                    [new Reference($this->buildContainerId($baseHandlerServiceId))]
                ]
            ]
        );

        $this->createService(
            $container,
            'initializer.logger_aware',
            LoggerAwareInitializer::class,
            [new Reference($this->buildContainerId('logger'))],
            ['context.initializer']
        );
        // SfKernelEventLogger
        if (true === $config['kernel']['debug']) {
            $this->createService(
                $container,
                'subscriber.sf_kernel_logger',
                SfKernelLoggerSubscriber::class,
                [new Reference($this->buildContainerId('logger.sf_kernel_logger'))],
                [EventDispatcherExtension::SUBSCRIBER_TAG]
            );
            $this->createService(
                $container,
                'logger.sf_kernel_logger',
                SfKernelEventLogger::class,
                [new Reference($this->buildContainerId('logger'))]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }
}
