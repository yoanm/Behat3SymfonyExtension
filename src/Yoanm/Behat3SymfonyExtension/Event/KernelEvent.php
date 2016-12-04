<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelEvent extends Event
{
    /** @var KernelInterface */
    private $kernel;
    /** @var string */
    private $name;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return KernelInterface
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
