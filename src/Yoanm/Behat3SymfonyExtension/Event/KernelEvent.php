<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

use Symfony\Component\HttpKernel\KernelInterface;

class KernelEvent extends AbstractEvent
{
    /** @var KernelInterface */
    private $kernel;

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
}
