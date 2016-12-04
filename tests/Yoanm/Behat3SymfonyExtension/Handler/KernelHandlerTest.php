<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Handler;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Kernel;
use Yoanm\Behat3SymfonyExtension\Event\Events;
use Yoanm\Behat3SymfonyExtension\Event\KernelEvent;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;

/**
 * Class KernelHandlerTest
 */
class KernelHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcherInterface|ObjectProphecy */
    private $behatEventDispatcher;
    /** @var Kernel|ObjectProphecy */
    private $sfKernel;
    /** @var KernelHandler */
    private $handler;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->behatEventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->sfKernel = $this->prophesize(Kernel::class);

        $this->handler = new KernelHandler(
            $this->behatEventDispatcher->reveal(),
            $this->sfKernel->reveal()
        );
    }

    public function testGetSfKernel()
    {
        $this->assertSame(
            $this->sfKernel->reveal(),
            $this->handler->getSfKernel()
        );
    }

    public function testShutdownSfKernel()
    {
        $this->prophesizeShutdownSfKernel();
        $this->handler->shutdownSfKernel();
    }

    public function testBootSfKernel()
    {
        $this->prophesizeBootSfKernel();
        $this->handler->bootSfKernel();
    }

    public function testRebootSfKernel()
    {
        $this->prophesizeBootSfKernel();
        $this->prophesizeShutdownSfKernel();
        $this->handler->rebootSfKernel();
    }

    protected function prophesizeShutdownSfKernel()
    {
        $this->sfKernel
            ->shutdown()
            ->shouldBeCalledTimes(1);
    }

    protected function prophesizeBootSfKernel()
    {
        $this->sfKernel
            ->boot()
            ->shouldBeCalledTimes(1);
    }
}
