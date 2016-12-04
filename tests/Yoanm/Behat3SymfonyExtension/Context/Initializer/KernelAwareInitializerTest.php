<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Context\KernelAwareInterface;

/**
 * Class KernelAwareInitializerTest
 */
class KernelAwareInitializerTest extends \PHPUnit_Framework_TestCase
{
    /** @var KernelInterface|ObjectProphecy */
    private $kernel;
    /** @var KernelAwareInitializer */
    private $initializer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->kernel = $this->prophesize(KernelInterface::class);

        $this->initializer = new KernelAwareInitializer(
            $this->kernel->reveal()
        );
    }

    public function testInitializeContextIfImplementInterface()
    {
        /** @var KernelAwareInterface|ObjectProphecy $context */
        $context = $this->prophesize(KernelAwareInterface::class);

        $context->setKernel($this->kernel->reveal())
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
