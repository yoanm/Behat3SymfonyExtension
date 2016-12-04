<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Subscriber;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Prophecy\Prophecy\ObjectProphecy;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;

/**
 * Class RebootKernelSubscriberTest
 */
class RebootKernelSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var KernelHandler|ObjectProphecy */
    private $kernelHandler;
    /** @var RebootKernelSubscriber */
    private $subscriber;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->kernelHandler = $this->prophesize(KernelHandler::class);

        $this->subscriber = new RebootKernelSubscriber(
            $this->kernelHandler->reveal()
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
        $this->kernelHandler->rebootSfKernel()
            ->shouldBeCalledTimes(1);

        $this->subscriber->rebootKernel();
    }
}
