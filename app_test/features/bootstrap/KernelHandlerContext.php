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
     * @inheritDoc
     */
    public function setKernelHandler(KernelHandler $kernelHandler)
    {
        $this->kernelHandler = $kernelHandler;
    }
}
