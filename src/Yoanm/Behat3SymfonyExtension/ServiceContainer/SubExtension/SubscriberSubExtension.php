<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;
use Yoanm\Behat3SymfonyExtension\Subscriber\SfKernelLoggerSubscriber;

class SubscriberSubExtension extends AbstractSubExtension
{
    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
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

        if (true === $config['kernel']['reboot']) {
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
    }
}
