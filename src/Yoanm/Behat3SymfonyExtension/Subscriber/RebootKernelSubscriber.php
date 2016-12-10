<?php
namespace Yoanm\Behat3SymfonyExtension\Subscriber;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Yoanm\Behat3SymfonyExtension\Client\Client;

/**
 * Class RebootKernelSubscriber
 */
class RebootKernelSubscriber implements EventSubscriberInterface
{
    /** @var Client */
    private $client;
    /** @var LoggerInterface */
    private $logger;

    /**
     *
     * @param Client          $client
     * @param LoggerInterface $logger
     */
    public function __construct(
        Client $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        //Register with the highest priority to reset the client (and so the kernel) before all others things
        return [
            ScenarioTested::BEFORE => ['reset', PHP_INT_MAX],
            ExampleTested::BEFORE => ['reset', PHP_INT_MAX],
        ];
    }

    public function reset()
    {
        // Resetting the client will also reboot the kernel
        $this->logger->debug('Resetting mink driver client');
        $this->client->resetClient();
    }
}
