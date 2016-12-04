<?php
namespace Yoanm\Behat3SymfonyExtension\Kernel;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Yoanm\Behat3SymfonyExtension\Context\BehatDispatcherAwareInterface;
use Yoanm\Behat3SymfonyExtension\Event\Events;
use Yoanm\Behat3SymfonyExtension\Event\KernelEvent;

class Kernel implements BehatDispatcherAwareInterface
{
    /** @var EventDispatcher */
    private $eventDispatcher;

    /**
     * @inheritDoc
     */
    public function setBehatDispatcher(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if (true === $this->booted) {
            return;
        }

        $event = new KernelEvent($this);
        $this->eventDispatcher->dispatch(
            Events::BEFORE_KERNEL_BOOT,
            $event
        );

        parent::boot();

        $this->eventDispatcher->dispatch(
            Events::AFTER_KERNEL_BOOT,
            $event
        );

    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        if (false === $this->booted) {
            return;
        }

        $event = new KernelEvent($this);
        $this->eventDispatcher->dispatch(
            Events::BEFORE_KERNEL_SHUTDOWN,

            $event
        );

        parent::shutdown();

        $this->eventDispatcher->dispatch(
            Events::AFTER_KERNEL_SHUTDOWN,

            $event
        );
    }
}
