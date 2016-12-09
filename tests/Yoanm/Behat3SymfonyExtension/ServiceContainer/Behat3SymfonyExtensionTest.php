<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Prophecy\Argument;
use Prophecy\Argument\Token;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

class Behat3SymfonyExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var Behat3SymfonyExtension */
    private $extension;

    /**
     * {@inheritDoc}
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
            $this->assertSetContainerParameterCalls(
                $container,
                sprintf('behat3_symfony_extension.kernel.%s', $key),
                $value
            );
        }
        foreach ($config['logger'] as $key => $value) {
            $this->assertSetContainerParameterCalls(
                $container,
                sprintf('behat3_symfony_extension.logger.%s', $key),
                $value
            );
        }

        $this->assertContainerAddResourceCalls($container, 'client.xml');
        $this->assertContainerAddResourceCalls($container, 'kernel.xml');
        $this->assertContainerAddResourceCalls($container, 'initializer.xml');
        $this->assertContainerAddResourceCalls($container, 'logger.xml');
        if (true === $config['kernel']['reboot']) {
            $this->assertContainerAddResourceCalls($container, 'kernel_auto_reboot.xml');
        }
        if (true === $config['kernel']['debug']) {
            $this->assertContainerAddResourceCalls($container, 'kernel_debug_mode.xml');
        }
    }

    public function testProcess()
    {
        $basePath = __DIR__;
        $bootstrap = 'Behat3SymfonyExtensionTest.php';
        $kernelPath = 'plop.html';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter('behat3_symfony_extension.kernel.bootstrap')
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->willReturn($basePath)
            ->shouldBeCalledTimes(1);

        $this->prophesizeProcessKernelFile($container, $basePath, $kernelPath);

        $this->extension->process($container->reveal());
    }

    public function testProcessWithoutPath()
    {
        $basePath = __DIR__;
        $bootstrap = null;
        $kernelPath = 'plop.html';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter('behat3_symfony_extension.kernel.bootstrap')
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->shouldNotBeCalled();

        $this->prophesizeProcessKernelFile($container, $basePath, $kernelPath);

        $this->extension->process($container->reveal());
    }

    public function testProcessWithInvalidFile()
    {
        $basePath = __DIR__;
        $bootstrap = 'invalid.php';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter('behat3_symfony_extension.kernel.bootstrap')
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->willReturn($basePath)
            ->shouldBeCalledTimes(1);

        $this->setExpectedException(ProcessingException::class, 'Could not find bootstrap file !');

        $this->extension->process($container->reveal());
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

    /**
     * @param ObjectProphecy|ContainerBuilder $container
     * @param string                          $fileName
     */
    protected function assertContainerAddResourceCalls(ObjectProphecy $container, $fileName)
    {
        $filePath = realpath(sprintf(
            '%s/%s/%s',
            __DIR__,
            '../../../../src/Yoanm/Behat3SymfonyExtension/Resources/config',
            $fileName
        ));
        $container->addResource(Argument::which('getResource', $filePath))
            ->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @param ObjectProphecy|ContainerBuilder $container
     * @param string                          $filePath
     */
    protected function assertSetContainerParameterCalls(ObjectProphecy $container, $key, $value)
    {
        $container->setParameter($key, $value)
            ->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @param string $serviceId
     *
     * @return Token\TokenInterface
     */
    protected function getReferenceAssertion($serviceId)
    {
        return Argument::allOf(
            Argument::type(Reference::class),
            Argument::which('__toString', $serviceId)
        );
    }

    /**
     * @param string $factoryServiceId
     * @param string $methodName
     *
     * @return Token\TokenInterface
     */
    protected function getFactoryServiceAssertion($factoryServiceId, $methodName)
    {
        return Argument::that(function (Definition $definition) use ($factoryServiceId, $methodName) {
            $factory = $definition->getFactory();
            $assertion = Argument::allOf(
                Argument::type('array'),
                // Check reference
                Argument::withEntry(
                    '0',
                    Argument::allOf(
                        Argument::type(Reference::class),
                        Argument::which('__toString', $factoryServiceId)
                    )
                ),
                // Check method name
                Argument::withEntry('1', $methodName)
            );
            return $assertion->scoreArgument($factory) === false ? false : true;
        });
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
            ->shouldBeCalledTimes(1);
        $container->getParameter('paths.base')
            ->willReturn($basePath)
            ->shouldBeCalledTimes(1);
        $container->getParameter('behat3_symfony_extension.kernel.path')
            ->willReturn($kernelPath)
            ->shouldBeCalledTimes(1);

        $definition->setFile($kernelPath)
            ->shouldBeCalledTimes(1);
    }
}
