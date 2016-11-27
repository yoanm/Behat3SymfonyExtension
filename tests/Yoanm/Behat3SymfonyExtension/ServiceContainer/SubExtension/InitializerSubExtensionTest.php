<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\BehatContextSubscriberInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelHandlerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\LoggerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\InitializerSubExtension;

class InitializerSubExtensionTest extends AbstractSubExtension
{
    /** @var InitializerSubExtension*/
    private $subExtension;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->subExtension = new InitializerSubExtension();
    }

    public function testLoad()
    {
        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->subExtension->load($container->reveal(), array());

        // KernelAware
        $this->assertCreateServiceCalls(
            $container,
            'initializer.kernel_aware',
            KernelHandlerAwareInitializer::class,
            array($this->getReferenceAssertion($this->getContainerParamOrServiceId('handler.kernel'))),
            array('context.initializer')
        );
        // LoggerAware
        $this->assertCreateServiceCalls(
            $container,
            'initializer.logger_aware',
            LoggerAwareInitializer::class,
            array($this->getReferenceAssertion($this->getContainerParamOrServiceId('logger'))),
            array('context.initializer')
        );
        // BehatSubscriber
        $this->assertCreateServiceCalls(
            $container,
            'initializer.behat_subscriber',
            BehatContextSubscriberInitializer::class,
            array($this->getReferenceAssertion('event_dispatcher')),
            array('context.initializer')
        );
    }
}
