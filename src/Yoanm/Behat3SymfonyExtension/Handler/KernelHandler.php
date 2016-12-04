<?php
namespace Yoanm\Behat3SymfonyExtension\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Kernel;

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
        $this->sfKernel->shutdown();
    }

    /**
     * Will boot sf kernel
     */
    public function bootSfKernel()
    {
        $this->sfKernel->boot();
    }
}
