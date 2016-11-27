<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\HandlerSubExtension;

class HandlerSubExtensionTests extends AbstractSubExtension
{
    /** @var HandlerSubExtension */
    private $subExtension;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->subExtension = new HandlerSubExtension();
    }

    public function testLoad()
    {
        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->subExtension->load($container->reveal(), array());

        $this->assertCreateServiceCalls(
            $container,
            'handler.kernel',
            KernelHandler::class,
            array(
                $this->getReferenceAssertion('event_dispatcher'),
                $this->getReferenceAssertion(Behat3SymfonyExtension::KERNEL_SERVICE_ID),
            )
        );
    }
}
