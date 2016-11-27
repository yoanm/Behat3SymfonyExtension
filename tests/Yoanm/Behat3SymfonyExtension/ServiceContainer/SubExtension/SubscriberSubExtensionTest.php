<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\SubscriberSubExtension;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;
use Yoanm\Behat3SymfonyExtension\Subscriber\SfKernelLoggerSubscriber;

class SubscriberSubExtensionTest extends AbstractSubExtension
{
    /** @var SubscriberSubExtension*/
    private $subExtension;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->subExtension = new SubscriberSubExtension();
    }

    /**
     * @dataProvider getTestLoadData
     *
     * @param bool $reboot
     */
    public function testLoad($reboot)
    {
        $config = array('kernel' => array('reboot' => $reboot));
        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);
        
        $this->subExtension->load($container->reveal(), $config);

        $this->assertCreateServiceCalls(
            $container,
            'subscriber.sf_kernel_logger',
            SfKernelLoggerSubscriber::class,
            array($this->getReferenceAssertion($this->getContainerParamOrServiceId('logger.sf_kernel_logger'))),
            array('event_dispatcher.subscriber')
        );

        $this->assertCreateServiceCalls(
            $container,
            'subscriber.reboot_kernel',
            RebootKernelSubscriber::class,
            array($this->getReferenceAssertion($this->getContainerParamOrServiceId('handler.kernel'))),
            array('event_dispatcher.subscriber'),
            array(),
            true === $reboot
        );
    }

    /**
     * @return array
     */
    public function getTestLoadData()
    {
        return array(
            'with reboot' => array(
                'reboot' => true,
            ),
            'without reboot' => array(
                'reboot' => false,
            ),
        );
    }
}
