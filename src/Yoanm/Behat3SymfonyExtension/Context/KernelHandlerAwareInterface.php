<?php
namespace Yoanm\Behat3SymfonyExtension\Context;

use Behat\Behat\Context\Context;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;

/**
 * Want to play with kernel (start/stop/...) ?
 * Just implement this interface and the Behat3SymfonyExtension kernelHandler will be injected
 */
interface KernelHandlerAwareInterface extends Context
{
    /**
     * @param KernelHandler $kernelHandler
     */
    public function setKernelHandler(KernelHandler $kernelHandler);
}
