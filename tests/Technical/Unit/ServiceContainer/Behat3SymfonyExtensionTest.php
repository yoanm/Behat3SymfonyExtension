<?php
namespace Technical\Unit\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Prophecy\Argument;
use Prophecy\Argument\Token;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

class Behat3SymfonyExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var Behat3SymfonyExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->extension = new Behat3SymfonyExtension();
    }

    public function testGetConfigKey()
    {
        $this->assertSame(
            'behat3_symfony',
            $this->extension->getConfigKey()
        );
    }

    public function testProcess()
    {
        $basePath = __DIR__;
        $bootstrap = 'Behat3SymfonyExtensionTest.php';
        $kernelPath = 'plop.html';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->prophesizeContainerParameterCalls($container, $bootstrap, $basePath, $kernelPath);

        $this->prophesizeProcessKernelFile($container, $basePath, $kernelPath);

        $this->extension->process($container->reveal());
    }
    public function testProcessWithFileUnderBasePath()
    {
        $basePath = __DIR__;
        $bootstrap = 'Behat3SymfonyExtensionTest.php';
        $kernelPath = 'Behat3SymfonyExtensionTest.php';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->prophesizeContainerParameterCalls($container, $bootstrap, $basePath, $kernelPath);

        $this->prophesizeProcessKernelFile($container, $basePath, $kernelPath);

        $this->extension->process($container->reveal());
    }
    public function testProcessWithoutBootstrap()
    {
        $basePath = __DIR__;
        $bootstrap = null;
        $kernelPath = 'plop.html';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->prophesizeContainerParameterCalls($container, $bootstrap, $basePath, $kernelPath);

        $this->prophesizeProcessKernelFile($container, $basePath, $kernelPath);

        $this->extension->process($container->reveal());
    }

    /**
     * @param ObjectProphecy|ContainerBuilder $container
     * @param string                          $basePath
     * @param string                          $kernelPath
     */
    protected function prophesizeProcessKernelFile(ObjectProphecy $container, $basePath, $kernelPath)
    {
        /** @var Definition|ObjectProphecy $definition */
        $definition = $this->prophesize(Definition::class);
        $container->getDefinition(Behat3SymfonyExtension::KERNEL_SERVICE_ID)
            ->willReturn($definition->reveal())
            ->shouldBeCalled();

        $kernelPathUnderBasePath = sprintf('%s/%s', $basePath, $kernelPath);

        if (file_exists($kernelPathUnderBasePath)) {
            $kernelPath = $kernelPathUnderBasePath;
        }

        $definition->setFile($kernelPath)->shouldBeCalled();
    }

    /**
     * @param $container
     * @param $bootstrap
     * @param $basePath
     */
    protected function prophesizeContainerParameterCalls($container, $bootstrap, $basePath, $kernelPath)
    {
        $container->getParameter('behat3_symfony_extension.kernel.bootstrap')
            ->willReturn($bootstrap)
            ->shouldBeCalled();
        $container->getParameter('paths.base')
            ->willReturn($basePath)
            ->shouldBeCalled();
        $container->getParameter('behat3_symfony_extension.kernel.path')
            ->willReturn($kernelPath)
            ->shouldBeCalled();
    }
}
