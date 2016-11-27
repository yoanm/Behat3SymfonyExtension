<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\KernelSubExtension;

class KernelSubExtensionTest extends AbstractSubExtension
{
    /** @var KernelSubExtension */
    private $subExtension;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->subExtension = new KernelSubExtension();
    }

    public function testGetConfigKey()
    {
        $this->assertSame(
            'kernel',
            $this->subExtension->getConfigKey()
        );
    }

    /**
     * @dataProvider getTestLoadData
     *
     * @param bool $reboot
     */
    public function testLoad($reboot)
    {
        $kernelConfig = array(
            'class' => 'class',
            'env' => 'test',
            'debug' => false,
            'reboot' => $reboot,
        );

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->subExtension->load($container->reveal(), array($this->subExtension->getConfigKey() => $kernelConfig));

        $container->setParameter($this->getContainerParamOrServiceId('kernel.reboot'), $kernelConfig['reboot'])
            ->shouldHaveBeenCalledTimes(1);
        $this->assertCreateServiceCalls(
            $container,
            'kernel',
            $kernelConfig['class'],
            array($kernelConfig['env'], $kernelConfig['debug'])
        );
    }

    public function testProcess()
    {
        $pathBase = __DIR__;
        $bootstrap = 'KernelSubExtensionTest.php';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter($this->getContainerParamOrServiceId('kernel.bootstrap'))
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->willReturn($pathBase)
            ->shouldBeCalledTimes(1);

        $this->subExtension->process($container->reveal());
    }

    public function testProcessWithoutPath()
    {
        $bootstrap = null;

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter($this->getContainerParamOrServiceId('kernel.bootstrap'))
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->shouldNotBeCalled();

        $this->subExtension->process($container->reveal());
    }

    public function testProcessWithInvalidFile()
    {
        $pathBase = __DIR__;
        $bootstrap = 'invalid.php';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter($this->getContainerParamOrServiceId('kernel.bootstrap'))
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->willReturn($pathBase)
            ->shouldBeCalledTimes(1);

        $this->setExpectedException(ProcessingException::class, 'Could not find bootstrap file !');

        $this->subExtension->process($container->reveal());
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
