<?php
namespace Technical\Unit\Yoanm\Behat3SymfonyExtension\Subscriber;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Kernel;
use Yoanm\Behat3SymfonyExtension\Event\Events;
use Yoanm\Behat3SymfonyExtension\Event\KernelEvent;
use Yoanm\Behat3SymfonyExtension\Logger\SfKernelEventLogger;
use Yoanm\Behat3SymfonyExtension\Subscriber\SfKernelLoggerSubscriber;

/**
 * Class SfKernelLoggerSubscriberTest
 */
class SfKernelLoggerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var SfKernelEventLogger|ObjectProphecy */
    private $sfKernelEventLogger;
    /** @var SfKernelLoggerSubscriber */
    private $subscriber;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->sfKernelEventLogger = $this->prophesize(SfKernelEventLogger::class);

        $this->subscriber = new SfKernelLoggerSubscriber(
            $this->sfKernelEventLogger->reveal()
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [Events::AFTER_KERNEL_BOOT => 'initSfKernelLogger'],
            SfKernelLoggerSubscriber::getSubscribedEvents()
        );
    }

    public function testInitSfKernelLogger()
    {
        /** @var KernelEvent|ObjectProphecy $event */
        $event = $this->prophesize(KernelEvent::class);
        /** @var Kernel|ObjectProphecy $kernel */
        $kernel = $this->prophesize(Kernel::class);
        /** @var ContainerInterface|ObjectProphecy $container */
        $container = $this->prophesize(ContainerInterface::class);
        /** @var EventDispatcher|ObjectProphecy $eventDispatcher */
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $event->getKernel()
            ->willReturn($kernel->reveal())
            ->shouldBeCalledTimes(1);
        $kernel->getContainer()
            ->willReturn($container->reveal())
            ->shouldBeCalledTimes(1);
        $container->get('event_dispatcher')
            ->willReturn($eventDispatcher->reveal())
            ->shouldBeCalledTimes(1);

        $eventDispatcher->addSubscriber($this->sfKernelEventLogger->reveal())
            ->shouldBeCalledTimes(1);

        $this->subscriber->initSfKernelLogger($event->reveal());
    }
}
