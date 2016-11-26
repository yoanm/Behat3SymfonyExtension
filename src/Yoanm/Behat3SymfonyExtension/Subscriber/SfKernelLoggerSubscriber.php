<?php
namespace Yoanm\Behat3SymfonyExtension\Subscriber;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Yoanm\Behat3SymfonyExtension\Event\KernelEvent;
use Yoanm\Behat3SymfonyExtension\Logger\SfKernelEventLogger;

/**
 * Class SfKernelLoggerSubscriber
 */
class SfKernelLoggerSubscriber implements EventSubscriberInterface
{
    /** @var SfKernelEventLogger */
    private $sfKernelEventLogger;

    /**
     * @param SfKernelEventLogger $sfKernelEventLogger
     */
    public function __construct(SfKernelEventLogger $sfKernelEventLogger)
    {
        $this->sfKernelEventLogger = $sfKernelEventLogger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvent::AFTER_BOOT => 'initSfKernelLogger',
        ];
    }

    /**
     * @param KernelEvent $event
     */
    public function initSfKernelLogger(KernelEvent $event)
    {
        /** @var EventDispatcherInterface $eventDispatcher the event dispatcher of SF application (not behat one)*/
        $eventDispatcher = $event->getKernel()->getContainer()->get('event_dispatcher');

        $eventDispatcher->addSubscriber($this->sfKernelEventLogger);
    }
}
