<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Prophecy\Argument;
use Prophecy\Argument\Token;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
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

        $container->getParameter('behat3_symfony_extension.kernel.bootstrap')
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->willReturn($basePath)
            ->shouldBeCalledTimes(2);

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

        $container->getParameter('behat3_symfony_extension.kernel.bootstrap')
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);
        $container->getParameter('paths.base')
            ->willReturn($basePath)
            ->shouldBeCalledTimes(2);

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

        $container->getParameter('behat3_symfony_extension.kernel.bootstrap')
            ->willReturn($bootstrap)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->willReturn($basePath)
            ->shouldBeCalledTimes(1);

        $this->prophesizeProcessKernelFile($container, $basePath, $kernelPath);

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
        $container->getParameter('behat3_symfony_extension.kernel.path')
            ->willReturn($kernelPath)
            ->shouldBeCalledTimes(1);

        $kernelPathUnderBasePath = sprintf('%s/%s', $basePath, $kernelPath);
        if (file_exists($kernelPathUnderBasePath)) {
            $kernelPath = $kernelPathUnderBasePath;
        }

        $definition->setFile($kernelPath)
            ->shouldBeCalledTimes(1);
    }

    /**
     * @param ContainerBuilder|ObjectProphecy $container
     * @param array                           $config
     * @param string                          $baseId
     */
    protected function prophesizeBindConfigToContainer(
        ObjectProphecy $container,
        array $config,
        $baseId = 'behat3_symfony_extension'
    ) {
        foreach ($config as $configKey => $configValue) {
            if (is_array($configValue)) {
                $this->prophesizeBindConfigToContainer(
                    $container,
                    $configValue,
                    sprintf('%s.%s', $baseId, $configKey)
                );
            } else {
                $container->setParameter(sprintf('%s.%s', $baseId, $configKey), $configValue)
                    ->shouldBeCalledTimes(1);
            }
        }
    }
}
