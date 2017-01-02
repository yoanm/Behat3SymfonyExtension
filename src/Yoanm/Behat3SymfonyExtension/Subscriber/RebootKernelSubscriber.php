<?php
namespace Yoanm\Behat3SymfonyExtension\Subscriber;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Yoanm\Behat3SymfonyExtension\Client\Client;
use Yoanm\BehatUtilsExtension\Subscriber\ListenerPriority;

/**
 * Class RebootKernelSubscriber
 */
class RebootKernelSubscriber implements EventSubscriberInterface
{
    /** @var Client */
    private $client;
    /** @var LoggerInterface|null */
    private $logger;
    /** @var bool */
    private $debugMode;

    /**
     *
     * @param Client          $client
     * @param LoggerInterface $logger
     * @param bool            $debugMode
     */
    public function __construct(
        Client $client,
        LoggerInterface $logger = null,
        $debugMode = false
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->debugMode = $debugMode;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        //Register with the highest priority to reset the client (and so the kernel) before all others things
        return [
            ScenarioTested::BEFORE => ['reset', ListenerPriority::HIGH_PRIORITY],
            ExampleTested::BEFORE => ['reset', ListenerPriority::HIGH_PRIORITY],
        ];
    }

    public function reset()
    {
        // Resetting the client will also reboot the kernel
        if (true === $this->debugMode) {
            $this->logger->debug('Resetting mink driver client');
        }
        $this->client->resetClient();
    }
}
