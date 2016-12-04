<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Kernel;

class KernelEvent extends Event
{
    /** @var Kernel */
    private $kernel;
    /** @var string */
    private $name;

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

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
