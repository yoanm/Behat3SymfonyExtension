<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Kernel;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Yoanm\Behat3SymfonyExtension\Event\Events;
use Yoanm\Behat3SymfonyExtension\Event\KernelEvent;

/**
 * Class KernelTest
 */
class KernelTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcher|ObjectProphecy */
    private $behatEventDispatcher;
    /** @var string */
    protected $environment = 'test';
    /** @var bool */
    protected $debug = false;
    /** @var KernelMock */
    private $kernel;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->behatEventDispatcher = $this->prophesize(EventDispatcher::class);

        $this->kernel = new KernelMock(
            $this->environment,
            $this->debug
        );

        $this->kernel->setBehatDispatcher($this->behatEventDispatcher->reveal());
    }

    public function testShutdownSfKernel()
    {
        //Kernel must be booted else no shutdown
        $this->kernel->boot();
        $this->prophesizeShutdownSfKernel();
        $this->kernel->shutdown();
    }

    public function testBootSfKernel()
    {
        $this->prophesizeBootSfKernel();
        $this->kernel->boot();
    }

    protected function prophesizeShutdownSfKernel()
    {
        $this->behatEventDispatcher
            ->dispatch(
                Events::BEFORE_KERNEL_SHUTDOWN,
                Argument::type(KernelEvent::class)
            )
            ->shouldBeCalledTimes(1);
        $this->behatEventDispatcher
            ->dispatch(
                Events::AFTER_KERNEL_SHUTDOWN,
                Argument::type(KernelEvent::class)
            )
            ->shouldBeCalledTimes(1);
    }

    protected function prophesizeBootSfKernel()
    {
        $this->behatEventDispatcher
            ->dispatch(
                Events::BEFORE_KERNEL_BOOT,
                Argument::type(KernelEvent::class)
            )
            ->shouldBeCalledTimes(1);
        $this->behatEventDispatcher
            ->dispatch(
                Events::AFTER_KERNEL_BOOT,
                Argument::type(KernelEvent::class)
            )
            ->shouldBeCalledTimes(1);
    }
}
