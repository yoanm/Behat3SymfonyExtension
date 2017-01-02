<?php
namespace Technical\Integration\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Monolog\Logger;
use Prophecy\Argument;
use Prophecy\Argument\Token;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

class Behat3SymfonyExtensionTest extends AbstractContainerBuilderTestCase
{
    /** @var Behat3SymfonyExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->extension = new Behat3SymfonyExtension();
    }

    public function testModulesConfigAppended()
    {
        $builder = new ArrayNodeDefinition('test');

        $this->extension->configure($builder);

        $config = (new Processor())->process($builder->getNode(true), []);

        $this->assertArrayHasKey('kernel', $config);
    }

    public function testDebugModeWithoutBehatUtilsExtensionLoaded()
    {
        $this->setExpectedException(\Exception::class, 'BehatUtilsExtension must be loaded before this one !');
        $this->loadContainer($this->getConfig(['debug_mode' => true]), false, false);
    }

    /**
     * @smokeTest
     * Will throw an exception if something goes wrong.
     * Like missing parameter, bad argument type, ...
     */
    public function testLoadable()
    {
        $this->assertNotEmpty($this->loadContainer());
    }

    public function testConfigurationBindedToContainerParameter()
    {
        // Don't use default configuration as it can change
        $config = [
            'debug_mode' => 'debug_mode',
            'kernel' => [
                'bootstrap' => 'bootstrap',
                'path' => 'path',
                'class' => 'class',
                'env' => 'env',
                'debug' => 'debug',
                'reboot' => 'reboot',
            ],
        ];
        $container = $this->loadContainer($config);

        $this->assertSame(
            $config['debug_mode'],
            $container->getParameter('behat3_symfony_extension.debug_mode')
        );
        $this->assertSame(
            $config['kernel']['bootstrap'],
            $container->getParameter('behat3_symfony_extension.kernel.bootstrap')
        );
        $this->assertSame(
            $config['kernel']['path'],
            $container->getParameter('behat3_symfony_extension.kernel.path')
        );
        $this->assertSame(
            $config['kernel']['class'],
            $container->getParameter('behat3_symfony_extension.kernel.class')
        );
        $this->assertSame(
            $config['kernel']['env'],
            $container->getParameter('behat3_symfony_extension.kernel.env')
        );
        $this->assertSame(
            $config['kernel']['debug'],
            $container->getParameter('behat3_symfony_extension.kernel.debug')
        );
        $this->assertSame(
            $config['kernel']['reboot'],
            $container->getParameter('behat3_symfony_extension.kernel.reboot')
        );
    }

    public function testServiceLoaded()
    {
        $container = $this->loadContainer();

        $serviceList = $container->getServiceIds();

        // Client
        $this->assertContains('behat3_symfony_extension.test.client', $serviceList);
        // Kernel
        $this->assertContains(Behat3SymfonyExtension::KERNEL_SERVICE_ID, $serviceList);
        // Initializer
        $this->assertContains('behat3_symfony_extension.initializer.kernel_aware', $serviceList);
    }

    public function testKernelAutoRebootLoadedIfEnabled()
    {
        $container = $this->loadContainer($this->getConfig(['kernel' => ['reload' => true]]));

        // Assert RebootKernelSubscriber is present (means 'kernel_auto_reboot.xml' has been loaded)
        $this->assertContains('behat3_symfony_extension.subscriber.reboot_kernel', $container->getServiceIds());
    }

    public function testKernelDebugLoadedIfEnabled()
    {
        $container = $this->loadContainer($this->getConfig(['kernel' => ['debug' => true]]));

        $this->assertKernelDebugLoaded($container);
    }

    /**
     * @group test
     */
    public function testConfigIsNormalized()
    {
        $container = $this->loadContainer(
            $this->getConfig(['debug_mode' => true, 'kernel' => ['debug' => false]])
        );

        // If debug mode => kernel debug mode is automatically overriden
        $this->assertKernelDebugLoaded($container);
    }

    /**
     * @param array $config  Extension config
     * @param bool  $process Wheter or not extension must be also processed before returning container
     *
     * @return ContainerBuilder
     */
    protected function loadContainer(array $config = null, $process = false, $fakeBehatUtilsExtension = true)
    {
        if (null == $config) {
            $config = $this->getConfig();
        }

        if (true === $fakeBehatUtilsExtension) {
            $this->setParameter('behat_utils_extension.logger.path', 'path');
        }

        $this->extension->load($this->container, $config);

        if (true === $process) {
            $this->extension->process($this->container);
        }

        $this->compile();

        return $this->container;
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function assertKernelDebugLoaded(ContainerBuilder $container)
    {
        // Assert SfKernelLoggerSubscriber is present (means 'kernel_debug_mode.xml' has been loaded)
        $this->assertContains('behat3_symfony_extension.subscriber.sf_kernel_logger', $container->getServiceIds());

        // Assert log level has been overriden
        $this->assertSame(Logger::DEBUG, $container->getParameter('behat_utils_extension.logger.level'));
    }

    /**
     * @param array $customConfig
     *
     * @return array
     */
    protected function getConfig(array $customConfig = [])
    {
        return array_replace_recursive(
            [
                'debug_mode' => false,
                'kernel' => [
                    'bootstrap' => 'app/autoload.php',
                    'path' => 'app/AppKernel.php',
                    'class' => 'AppKernel',
                    'env' => 'test',
                    'debug' => true,
                    'reboot' => true,
                ]
            ],
            $customConfig
        );
    }
}
