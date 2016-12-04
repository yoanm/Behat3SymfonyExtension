<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Subscriber;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;

/**
 * Class RebootKernelSubscriberTest
 */
class RebootKernelSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var KernelInterface|ObjectProphecy */
    private $kernel;
    /** @var RebootKernelSubscriber */
    private $subscriber;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->kernel = $this->prophesize(KernelInterface::class);

        $this->subscriber = new RebootKernelSubscriber(
            $this->kernel->reveal()
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [
                ScenarioTested::BEFORE => 'rebootKernel',
                ExampleTested::BEFORE => 'rebootKernel',
            ],
            RebootKernelSubscriber::getSubscribedEvents()
        );
    }

    public function testRebootKernel()
    {
        $this->kernel->shutdown()
            ->shouldBeCalledTimes(1);
        $this->kernel->boot()
            ->shouldBeCalledTimes(1);

        $this->subscriber->rebootKernel();
    }
}
