<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\BehatContextSubscriberInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelHandlerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\LoggerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\KernelSubExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\LoggerSubExtension;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;

class Behat3SymfonyExtensionTest extends AbstractExtensionTest
{
    /** @var KernelSubExtension|ObjectProphecy */
    private $kernelSubExtension;
    /** @var LoggerSubExtension|ObjectProphecy */
    private $loggerSubExtension;
    /** @var Behat3SymfonyExtension */
    private $extension;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->kernelSubExtension = $this->prophesize(KernelSubExtension::class);
        $this->loggerSubExtension = $this->prophesize(LoggerSubExtension::class);
        $this->extension = new Behat3SymfonyExtension(
            $this->kernelSubExtension->reveal(),
            $this->loggerSubExtension->reveal()
        );
    }

    public function testGetConfigKey()
    {
        $this->assertSame(
            'behat3_symfony',
            $this->extension->getConfigKey()
        );
    }

    public function testConfigure()
    {
        $kernelSubExtensionConfigKey = 'kernel';
        $loggerSubExtensionConfigKey = 'logger';
        /** @var ArrayNodeDefinition|ObjectProphecy $subExtensionBuilder */
        $subExtensionBuilder = $this->prophesize(ArrayNodeDefinition::class);
        /** @var ArrayNodeDefinition|ObjectProphecy $extensionBuilder */
        $extensionBuilder = $this->prophesize(ArrayNodeDefinition::class);
        /** @var NodeBuilder|ObjectProphecy $nodeBuilder */
        $nodeBuilder = $this->prophesize(NodeBuilder::class);

        $extensionBuilder->children()
            ->willReturn($nodeBuilder->reveal())
            ->shouldBeCalled();
        $nodeBuilder->arrayNode($kernelSubExtensionConfigKey)
            ->willReturn($subExtensionBuilder->reveal())
            ->shouldBeCalledTimes(1);
        $nodeBuilder->arrayNode($loggerSubExtensionConfigKey)
            ->willReturn($subExtensionBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $this->kernelSubExtension->configure($subExtensionBuilder->reveal())
            ->shouldBeCalledTimes(1);
        $this->loggerSubExtension->configure($subExtensionBuilder->reveal())
            ->shouldBeCalledTimes(1);

        $this->kernelSubExtension->getConfigKey()
            ->willReturn($kernelSubExtensionConfigKey)
            ->shouldBeCalledTimes(1);
        $this->loggerSubExtension->getConfigKey()
            ->willReturn($loggerSubExtensionConfigKey)
            ->shouldBeCalledTimes(1);

        $this->extension->configure($extensionBuilder->reveal());
    }

    /**
     * @dataProvider getTestLoadData
     *
     * @param bool $reboot
     */
    public function testLoad($reboot)
    {
        $config = [
            'kernel' => [
                'class' => 'class',
                'env' => 'test',
                'debug' => false,
                'reboot' => $reboot,
                'bootstrap' => null,
            ],
            'logger' => [
                'path' => 'path',
                'level' => 'level'
            ],
        ];
        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->kernelSubExtension->load($container->reveal(), $config)
            ->shouldBeCalledTimes(1);
        $this->loggerSubExtension->load($container->reveal(), $config)
            ->shouldBeCalledTimes(1);

        $this->assertNull($this->extension->load($container->reveal(), $config));

        $this->assertCreateServiceCalls(
            $container,
            'handler.kernel',
            KernelHandler::class,
            [
                $this->getReferenceAssertion('event_dispatcher'),
                $this->getReferenceAssertion(Behat3SymfonyExtension::KERNEL_SERVICE_ID),
            ]
        );
        // KernelAware
        $this->assertCreateServiceCalls(
            $container,
            'initializer.kernel_aware',
            KernelHandlerAwareInitializer::class,
            [$this->getReferenceAssertion($this->buildContainerId('handler.kernel'))],
            ['context.initializer']
        );
        // LoggerAware
        $this->assertCreateServiceCalls(
            $container,
            'initializer.logger_aware',
            LoggerAwareInitializer::class,
            [$this->getReferenceAssertion($this->buildContainerId('logger'))],
            ['context.initializer']
        );
        // BehatSubscriber
        $this->assertCreateServiceCalls(
            $container,
            'initializer.behat_subscriber',
            BehatContextSubscriberInitializer::class,
            [$this->getReferenceAssertion('event_dispatcher')],
            ['context.initializer']
        );

        $this->assertCreateServiceCalls(
            $container,
            'subscriber.reboot_kernel',
            RebootKernelSubscriber::class,
            [$this->getReferenceAssertion($this->buildContainerId('handler.kernel'))],
            ['event_dispatcher.subscriber'],
            null,
            true === $reboot
        );
    }

    public function testProcess()
    {
        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->kernelSubExtension->process($container->reveal())
            ->shouldBeCalledTimes(1);
        $this->loggerSubExtension->process($container->reveal())
            ->shouldBeCalledTimes(1);

        $this->assertNull($this->extension->process($container->reveal()));
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
}
