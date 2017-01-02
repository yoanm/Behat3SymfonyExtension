<?php
namespace Technical\Unit\Yoanm\Behat3SymfonyExtension\Subscriber;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Yoanm\Behat3SymfonyExtension\Client\Client;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;
use Yoanm\BehatUtilsExtension\Subscriber\ListenerPriority;

/**
 * Class RebootKernelSubscriberTest
 */
class RebootKernelSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var Client|ObjectProphecy */
    private $client;
    /** @var LoggerInterface|ObjectProphecy */
    private $logger;
    /** @var RebootKernelSubscriber */
    private $subscriber;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->client = $this->prophesize(Client::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->subscriber = new RebootKernelSubscriber(
            $this->client->reveal(),
            $this->logger->reveal(),
            true
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [
                ScenarioTested::BEFORE => ['reset', ListenerPriority::HIGH_PRIORITY],
                ExampleTested::BEFORE => ['reset', ListenerPriority::HIGH_PRIORITY],
            ],
            RebootKernelSubscriber::getSubscribedEvents()
        );
    }

    public function testReset()
    {
        $this->client->resetClient()->shouldBeCalled();
        $this->subscriber->reset();
    }
}
