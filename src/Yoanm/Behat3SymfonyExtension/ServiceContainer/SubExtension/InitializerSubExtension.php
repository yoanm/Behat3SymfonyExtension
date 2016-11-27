<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\BehatContextSubscriberInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelHandlerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\LoggerAwareInitializer;

class InitializerSubExtension extends AbstractSubExtension
{
    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
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
}
