<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Prophecy\Prophecy\ObjectProphecy;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelHandlerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Context\KernelHandlerAwareInterface;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;

/**
 * Class KernelHandlerAwareInitializerTest
 */
class KernelHandlerAwareInitializerTest extends \PHPUnit_Framework_TestCase
{
    /** @var KernelHandler|ObjectProphecy */
    private $kernelHandler;
    /** @var KernelHandlerAwareInitializer */
    private $initializer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->kernelHandler = $this->prophesize(KernelHandler::class);

        $this->initializer = new KernelHandlerAwareInitializer(
            $this->kernelHandler->reveal()
        );
    }

    public function testInitializeContextIfImplementInterface()
    {
        /** @var KernelHandlerAwareInterface|ObjectProphecy $context */
        $context = $this->prophesize(KernelHandlerAwareInterface::class);

        $context->setKernelHandler($this->kernelHandler->reveal())
            ->shouldBeCalledTimes(1);

        $this->initializer->initializeContext($context->reveal());
    }

    public function testInitializeContextIfNotImplementInterface()
    {
        /** @var Context|ObjectProphecy $context */
        $context = $this->prophesize(Context::class);

        $this->assertNull(
            $this->initializer->initializeContext($context->reveal())
        );
    }
}
