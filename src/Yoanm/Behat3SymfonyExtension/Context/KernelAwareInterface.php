<?php
namespace Yoanm\Behat3SymfonyExtension\Context;

use Behat\Behat\Context\Context;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Want to play with kernel ?
 * Just implement this interface and the your symfony app kernel will be injected
 * To boot/shutdown/restart kernel, please use KernelHandler instance (see KernelHandlerAwareInterface)
 */
interface KernelAwareInterface extends Context
{
    /**
     * @param KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel);
}
