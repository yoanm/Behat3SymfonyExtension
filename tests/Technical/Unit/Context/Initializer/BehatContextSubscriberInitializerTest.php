<?php
namespace Technical\Unit\Yoanm\Behat3SymfonyExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Yoanm\Behat3SymfonyExtension\Context\BehatContextSubscriberInterface;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\BehatContextSubscriberInitializer;

/**
 * Class BehatContextSubscriberInitializerTest
 */
class BehatContextSubscriberInitializerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcherInterface|ObjectProphecy */
    private $behatEventDispatcher;
    /** @var BehatContextSubscriberInitializer */
    private $initializer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->behatEventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->initializer = new BehatContextSubscriberInitializer(
            $this->behatEventDispatcher->reveal()
        );
    }

    public function testInitializeContextIfImplementInterface()
    {
        /** @var BehatContextSubscriberInterface|ObjectProphecy $context */
        $context = $this->prophesize(BehatContextSubscriberInterface::class);

        $this->behatEventDispatcher->addSubscriber($context)
            ->shouldBeCalledTimes(1);

        $this->initializer->initializeContext($context->reveal());
    }

    public function testInitializeContextIfNotImplementInterface()
    {
        /** @var Context|ObjectProphecy $context */
        $context = $this->prophesize(Context::class);

        $this->behatEventDispatcher->addSubscriber($context)
            ->shouldNotBeCalled();

        $this->initializer->initializeContext($context->reveal());
    }
}
