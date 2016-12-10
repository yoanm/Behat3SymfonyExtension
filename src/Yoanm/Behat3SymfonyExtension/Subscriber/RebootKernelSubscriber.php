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
     * @param Client $client
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
        return [
            ScenarioTested::BEFORE => 'reset',
            ExampleTested::BEFORE => 'reset',
        ];
    }

    public function reset()
    {
        // Resetting the client will also reboot the kernel
        $this->logger->debug('Resetting mink driver client');
        $this->client->resetClient();
    }
}
