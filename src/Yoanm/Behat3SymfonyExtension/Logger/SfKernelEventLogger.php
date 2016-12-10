<?php
namespace Yoanm\Behat3SymfonyExtension\Logger;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This class will log on :
 * - kernel.request event => log that a request has been handled
 * - kernel.exception event => log that an exception has been thrown
 *
 * It's really usefull to understand what happens behind the scene when a behat step is executed
 */
class SfKernelEventLogger implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @throws \Exception
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->logger->info(
            '[REQUEST]',
            [
                'type' => ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST
                    ? 'Master'
                    : 'Sub'
                ),
                'method' => $event->getRequest()->getMethod(),
                'uri' => $event->getRequest()->getUri(),
            ]
        );
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $this->logger->error(
            '[EXCEPTION_THROWN]',
            [
                'message' => $event->getException()->getMessage(),
            ]
        );
    }
}
