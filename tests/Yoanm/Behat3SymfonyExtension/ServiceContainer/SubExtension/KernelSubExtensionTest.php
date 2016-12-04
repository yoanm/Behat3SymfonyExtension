<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Prophecy\Argument;
use Prophecy\Argument\Token;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\AbstractExtensionTest;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Dispatcher\BehatKernelEventDispatcher;
use Yoanm\Behat3SymfonyExtension\Factory\KernelFactory;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\AbstractExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\KernelSubExtension;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;

class KernelSubExtensionTest extends AbstractExtensionTest
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
        $kernelConfig = [
            'class' => 'class',
            'env' => 'test',
            'debug' => false,
            'reboot' => $reboot,
            'bootstrap' => 'bootstrap',
            'path' => 'path',
        ];

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->subExtension->load($container->reveal(), [$this->subExtension->getConfigKey() => $kernelConfig]);

        $container->setParameter($this->buildContainerId('kernel.reboot'), $kernelConfig['reboot'])
            ->shouldHaveBeenCalledTimes(1);
        $container->setParameter($this->buildContainerId('kernel.bootstrap'), $kernelConfig['bootstrap'])
            ->shouldHaveBeenCalledTimes(1);
        $container->setParameter($this->buildContainerId('kernel.path'), $kernelConfig['path'])
            ->shouldHaveBeenCalledTimes(1);
        $this->assertCreateServiceCalls(
            $container,
            'kernel',
            $kernelConfig['class'],
            null,
            [],
            null,
            $this->getFactoryServiceAssertion($this->buildContainerId('factory.kernel'), 'load')
        );
        $this->assertCreateServiceCalls(
            $container,
            'dispatcher.kernel_event',
            BehatKernelEventDispatcher::class,
            [$this->getReferenceAssertion('event_dispatcher')]
        );
        $this->assertCreateServiceCalls(
            $container,
            'factory.kernel',
            KernelFactory::class,
            [
                $this->getReferenceAssertion($this->buildContainerId('dispatcher.kernel_event')),
                '%'.$this->buildContainerId('kernel.path').'%',
                '%'.$this->buildContainerId('kernel.class').'%',
                '%'.$this->buildContainerId('kernel.env').'%',
                '%'.$this->buildContainerId('kernel.debug').'%'
            ]
        );
        // KernelAware
        $this->assertCreateServiceCalls(
            $container,
            'initializer.kernel_aware',
            KernelAwareInitializer::class,
            [$this->getReferenceAssertion(AbstractExtension::KERNEL_SERVICE_ID)],
            ['context.initializer']
        );
        $this->assertCreateServiceCalls(
            $container,
            'subscriber.reboot_kernel',
            RebootKernelSubscriber::class,
            [$this->getReferenceAssertion($this->buildContainerId('handler.kernel'))],
            [EventDispatcherExtension::SUBSCRIBER_TAG],
            null,
            null,
            true === $reboot
        );
    }

    public function testProcess()
    {
        $pathBase = __DIR__;
        $bootstrap = 'KernelSubExtensionTest.php';
        $kernelPath = 'plop.html';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter($this->buildContainerId('kernel.bootstrap'))
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->willReturn($pathBase)
            ->shouldBeCalledTimes(1);

        $this->prophesizeProcessKernelFile($container, $pathBase, $kernelPath);

        $this->subExtension->process($container->reveal());
    }

    public function testProcessWithoutPath()
    {
        $pathBase = __DIR__;
        $bootstrap = null;
        $kernelPath = 'plop.html';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter($this->buildContainerId('kernel.bootstrap'))
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->shouldNotBeCalled();

        $this->prophesizeProcessKernelFile($container, $pathBase, $kernelPath);

        $this->subExtension->process($container->reveal());
    }

    public function testProcessWithInvalidFile()
    {
        $pathBase = __DIR__;
        $bootstrap = 'invalid.php';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter($this->buildContainerId('kernel.bootstrap'))
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
        return [
            'with reboot' => [
                'reboot' => true,
            ],
            'without reboot' => [
                'reboot' => false,
            ],
        ];
    }

    protected function prophesizeProcessKernelFile(ObjectProphecy $container, $basePath, $kernelPath)
    {
        /** @var Definition|ObjectProphecy $definition */
        $definition = $this->prophesize(Definition::class);

        $container->getDefinition(AbstractExtension::KERNEL_SERVICE_ID)
            ->willReturn($definition->reveal())
            ->shouldBeCalledTimes(1);
        $container->getParameter('paths.base')
            ->willReturn($basePath)
            ->shouldBeCalledTimes(1);
        $container->getParameter($this->buildContainerId('kernel.path'))
            ->willReturn($kernelPath)
            ->shouldBeCalledTimes(1);

        $definition->setFile($kernelPath)
            ->shouldBeCalledTimes(1);
    }
}
