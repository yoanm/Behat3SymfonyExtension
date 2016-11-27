<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Logger\SfKernelEventLogger;

class LoggerSubExtension extends AbstractSubExtension
{
    /**
     * @inheritDoc
     */
    public function getConfigKey()
    {
        return 'logger';
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
            array(
                sprintf(
                    '%s/%s',
                    '%behat.paths.base%',
                    $loggerConfig['path']
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
}
