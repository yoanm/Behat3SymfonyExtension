<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

class HandlerSubExtension extends AbstractSubExtension
{
    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $this->createService(
            $container,
            'handler.kernel',
            KernelHandler::class,
            array(
                new Reference('event_dispatcher'),
                new Reference(Behat3SymfonyExtension::KERNEL_SERVICE_ID),
            )
        );
    }
}
