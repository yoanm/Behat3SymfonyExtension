<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Kernel;

class KernelEvent extends Event
{
    const BEFORE_SHUTDOWN = 'kernel_event.before.shutdown';
    const AFTER_SHUTDOWN = 'kernel_event.after.shutdown';
    const BEFORE_BOOT = 'kernel_event.before.boot';
    const AFTER_BOOT = 'kernel_event.after.boot';

    /** @var Kernel */
    private $kernel;

    /**
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }
}
