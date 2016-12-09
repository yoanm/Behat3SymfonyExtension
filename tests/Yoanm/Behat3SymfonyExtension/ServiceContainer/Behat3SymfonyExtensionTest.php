<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\KernelSubExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\LoggerSubExtension;

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
     * @param bool $debug
     */
    public function testLoad($reboot, $debug)
    {
        $config = [
            'kernel' => [
                'class' => 'class',
                'env' => 'test',
                'debug' => $debug,
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

        $this->assertNull($this->extension->load($container->reveal(), $config));

        foreach ($config['kernel'] as $key => $value) {
            $this->assertSetContainerParameter(
                $container,
                $this->buildContainerId(sprintf('kernel.%s', $key)),
                $value
            );
        }
        foreach ($config['logger'] as $key => $value) {
            $this->assertSetContainerParameter(
                $container,
                $this->buildContainerId(sprintf('logger.%s', $key)),
                $value
            );
        }

        $this->assertContainerAddResource($container, 'client.xml');
        $this->assertContainerAddResource($container, 'kernel.xml');
        $this->assertContainerAddResource($container, 'initializer.xml');
        $this->assertContainerAddResource($container, 'logger.xml');
        if (true === $config['kernel']['reboot']) {
            $this->assertContainerAddResource($container, 'kernel_auto_reboot.xml');
        }
        if (true === $config['kernel']['debug']) {
            $this->assertContainerAddResource($container, 'kernel_debug_mode.xml');
        }
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
            'with reboot / debug mode off' => [
                'reboot' => true,
                'debug' => false,
            ],
            'without reboot / debug mode off' => [
                'reboot' => false,
                'debug' => false,
            ],
            'with reboot / debug mode on' => [
                'reboot' => true,
                'debug' => true,
            ],
            'without reboot / debug mode on' => [
                'reboot' => false,
                'debug' => true,
            ],
        ];
    }
}
