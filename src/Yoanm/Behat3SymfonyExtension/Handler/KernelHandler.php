<?php
namespace Yoanm\Behat3SymfonyExtension\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Kernel;
use Yoanm\Behat3SymfonyExtension\Event\Events;
use Yoanm\Behat3SymfonyExtension\Event\KernelEvent;

/**
 * Handler to shutdown/boot sf kernel
 */
class KernelHandler
{
    /** @var EventDispatcherInterface */
    private $behatEventDispatcher;

    /** @var Kernel */
    private $sfKernel;
    /**
     * @param EventDispatcherInterface $behatEventDispatcher
     * @param Kernel                   $sfKernel
     */
    public function __construct(
        EventDispatcherInterface $behatEventDispatcher,
        Kernel $sfKernel
    ) {
        $this->behatEventDispatcher = $behatEventDispatcher;
        $this->sfKernel = $sfKernel;
    }

    /**
     * @return Kernel
     */
    public function getSfKernel()
    {
        return $this->sfKernel;
    }

    /**
     * Will reboot sf kernel
     */
    public function rebootSfKernel()
    {
        $this->shutdownSfKernel();
        $this->bootSfKernel();
    }

    /**
     * Will shutdown sf kernel
     */
    public function shutdownSfKernel()
    {
        $event = new KernelEvent($this->sfKernel);
        $this->behatEventDispatcher->dispatch(
            Events::BEFORE_KERNEL_SHUTDOWN,
            $event
        );
        $this->sfKernel->shutdown();
        $this->behatEventDispatcher->dispatch(
            Events::AFTER_KERNEL_SHUTDOWN,
            $event
        );
    }

    /**
     * Will boot sf kernel
     */
    public function bootSfKernel()
    {
        $event = new KernelEvent($this->sfKernel);
        $this->behatEventDispatcher->dispatch(
            Events::BEFORE_KERNEL_BOOT,
            $event
        );
        $this->sfKernel->boot();
        $this->behatEventDispatcher->dispatch(
            Events::AFTER_KERNEL_BOOT,
            $event
        );
    }
}
