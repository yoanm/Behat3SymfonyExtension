<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Dispatcher;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Dispatcher\BehatKernelEventDispatcher;
use Yoanm\Behat3SymfonyExtension\Event\Events;
use Yoanm\Behat3SymfonyExtension\Event\KernelEvent;

class BehatKernelEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcherInterface|ObjectProphecy */
    private $behatEventDispatcher;
    /** @var BehatKernelEventDispatcher */
    private $dispatcher;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->behatEventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->dispatcher = new BehatKernelEventDispatcher($this->behatEventDispatcher->reveal());
    }

    public function testBeforeBoot()
    {
        $kernel = $this->prophesizeEventDispatch(Events::BEFORE_KERNEL_BOOT);

        $this->dispatcher->beforeBoot($kernel->reveal());
    }

    public function testAfterBoot()
    {
        $kernel = $this->prophesizeEventDispatch(Events::AFTER_KERNEL_BOOT);

        $this->dispatcher->afterBoot($kernel->reveal());
    }

    public function testBeforeShutdown()
    {
        $kernel = $this->prophesizeEventDispatch(Events::BEFORE_KERNEL_SHUTDOWN);

        $this->dispatcher->beforeShutdown($kernel->reveal());
    }

    public function testAfterShutdown()
    {
        $kernel = $this->prophesizeEventDispatch(Events::AFTER_KERNEL_SHUTDOWN);

        $this->dispatcher->afterShutdown($kernel->reveal());
    }

    /**
     * @param string $eventName
     *
     * @return ObjectProphecy|KernelInterface
     */
    protected function prophesizeEventDispatch($eventName)
    {
        /** @var KernelInterface|ObjectProphecy $kernel */
        $kernel = $this->prophesize(KernelInterface::class);
        $this->behatEventDispatcher
            ->dispatch(
                $eventName,
                Argument::allOf(
                    Argument::type(KernelEvent::class),
                    Argument::which('getKernel', $kernel->reveal())
                )
            )
            ->shouldBeCalledTimes(1);

        return $kernel;
    }
}
