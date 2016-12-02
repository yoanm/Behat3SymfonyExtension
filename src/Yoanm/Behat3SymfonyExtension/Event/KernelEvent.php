<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Kernel;

class KernelEvent extends Event
{
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
