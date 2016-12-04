<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Yoanm\Behat3SymfonyExtension\Context\KernelHandlerAwareInterface;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;

class KernelHandlerContext implements Context, KernelHandlerAwareInterface
{
    /** @var KernelHandler */
    private $kernelHandler;

    /**
     * @Then I shutdown symfony kernel
     */
    public function iCanShutdownSymfonyKernel()
    {
        $this->kernelHandler->shutdownSfKernel();
    }

    /**
     * @When I reboot symfony kernel
     */
    public function iRebootSymfonyKernel()
    {
        $this->kernelHandler->rebootSfKernel();
    }

    /**
     * @Then I boot symfony kernel
     */
    public function iCanBootSymfonyKernel()
    {
        $this->kernelHandler->bootSfKernel();
    }

    /**
     * @inheritDoc
     */
    public function setKernelHandler(KernelHandler $kernelHandler)
    {
        $this->kernelHandler = $kernelHandler;
    }
}
