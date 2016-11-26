<?php
namespace Yoanm\Behat3SymfonyExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Yoanm\Behat3SymfonyExtension\Context\KernelHandlerAwareInterface;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;

/**
 * Class KernelHandlerAwareInitializer
 */
class KernelHandlerAwareInitializer implements ContextInitializer
{
    /** @var KernelHandler */
    private $kernelHandler;

    /**
     * @param KernelHandler $kernelHandler
     */
    public function __construct(KernelHandler $kernelHandler)
    {
        $this->kernelHandler = $kernelHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        if (!$context instanceof KernelHandlerAwareInterface) {
            return;
        }

        $context->setKernelHandler($this->kernelHandler);
    }
}
