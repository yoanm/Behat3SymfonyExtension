<?php
namespace Yoanm\Behat3SymfonyExtension\Dispatcher;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Event\Events;
use Yoanm\Behat3SymfonyExtension\Event\KernelEvent;

/**
 * Will dispatch events related to symfony app kernel
 * It's just a wrapper to have the minimum requirement in the AppKernel mock
 *
 * @see Yoanm\Behat3SymfonyExtension\Factory\KernelFactory::load()
 */
class BehatKernelEventDispatcher
{
    /** @var EventDispatcherInterface */
    private $behatEventDispatcher;

    /**
     * @param EventDispatcherInterface $behatEventDispatcher
     */
    public function __construct(EventDispatcherInterface $behatEventDispatcher)
    {
        $this->behatEventDispatcher = $behatEventDispatcher;
    }

    /**
     * @param KernelInterface $kernel
     */
    public function beforeBoot(KernelInterface $kernel)
    {
        $this->behatEventDispatcher->dispatch(
            Events::BEFORE_KERNEL_BOOT,
            new KernelEvent($kernel)
        );
    }

    /**
     * @param KernelInterface $kernel
     */
    public function afterBoot(KernelInterface $kernel)
    {
        $this->behatEventDispatcher->dispatch(
            Events::AFTER_KERNEL_BOOT,
            new KernelEvent($kernel)
        );
    }

    /**
     * @param KernelInterface $kernel
     */
    public function beforeShutdown(KernelInterface $kernel)
    {
        $this->behatEventDispatcher->dispatch(
            Events::BEFORE_KERNEL_SHUTDOWN,
            new KernelEvent($kernel)
        );
    }

    /**
     * @param KernelInterface $kernel
     */
    public function afterShutdown(KernelInterface $kernel)
    {
        $this->behatEventDispatcher->dispatch(
            Events::AFTER_KERNEL_SHUTDOWN,
            new KernelEvent($kernel)
        );
    }
}
