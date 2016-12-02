<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Behat\Testwork\ServiceContainer\ExtensionManager;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Logger\SfKernelEventLogger;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\AbstractExtension;

class LoggerSubExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getConfigKey()
    {
        return 'logger';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }


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

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $loggerConfig = $config[$this->getConfigKey()];
        $baseHandlerServiceId = 'logger.handler';
        // Handler
        $this->createService(
            $container,
            $baseHandlerServiceId,
            StreamHandler::class,
            [
                sprintf(
                    '%s/%s',
                    '%behat.paths.base%',
                    $loggerConfig['path']
                ),
                $loggerConfig['level'],
            ]
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
                    array(new Reference($this->buildContainerId($baseHandlerServiceId)))
                )
            )
        );
        // SfKernelEventLogger
        $this->createService(
            $container,
            'logger.sf_kernel_logger',
            SfKernelEventLogger::class,
            array(
                new Reference($this->buildContainerId('kernel'))
            ),
            array('event_dispatcher.subscriber')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }
}
