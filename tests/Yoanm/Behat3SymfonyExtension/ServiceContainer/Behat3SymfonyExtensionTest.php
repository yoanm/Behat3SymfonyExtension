<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Prophecy\Argument;
use Prophecy\Argument\Token;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\BehatContextSubscriberInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\KernelHandlerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\LoggerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;
use Yoanm\Behat3SymfonyExtension\Logger\SfKernelEventLogger;
use Yoanm\Behat3SymfonyExtension\Subscriber\RebootKernelSubscriber;
use Yoanm\Behat3SymfonyExtension\Subscriber\SfKernelLoggerSubscriber;

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
     */
    public function testLoad($reboot)
    {
        $config = array(
            'kernel' => array(
                'class' => 'class',
                'env' => 'test',
                'debug' => false,
                'reboot' => $reboot,
            ),
            'logger' => array(
                'path' => 'path',
                'level' => 'level'
            ),
        );

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->extension->load($container->reveal(), $config);

        $this->assertLoadKernelCalls($container, $config['kernel']);
        $this->assertLoadLoggerCalls($container, $config['logger']);
        $this->assertLoadHandlerCalls($container);
        $this->assertLoadInitializerCalls($container);
        $this->assertLoadSubscriberCalls($container, $config['kernel']);
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

    public function testProcess()
    {
        $pathBase = __DIR__;
        $bootstrapPath = 'Behat3SymfonyExtensionTest.php';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter($this->getContainerParamOrServiceId('kernel.bootstrap'))
            ->willReturn($bootstrapPath)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->willReturn($pathBase)
            ->shouldBeCalledTimes(1);

        $this->extension->process($container->reveal());
    }

    public function testProcessWithoutPath()
    {
        $bootstrapPath = null;

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter($this->getContainerParamOrServiceId('kernel.bootstrap'))
            ->willReturn($bootstrapPath)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->shouldNotBeCalled();

        $this->extension->process($container->reveal());
    }

    public function testProcessWithInvalidFile()
    {
        $pathBase = __DIR__;
        $bootstrapPath = 'invalid.php';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $container->getParameter($this->getContainerParamOrServiceId('kernel.bootstrap'))
            ->willReturn($bootstrapPath)
            ->shouldBeCalledTimes(1);

        $container->getParameter('paths.base')
            ->willReturn($pathBase)
            ->shouldBeCalledTimes(1);

        $this->setExpectedException(ProcessingException::class, 'Could not find bootstrap file !');

        $this->extension->process($container->reveal());
    }

    /**
     * @param ObjectProphecy|ContainerInterface $container
     * @param array                             $kernelConfig
     */
    protected function assertLoadKernelCalls(ObjectProphecy $container, array $kernelConfig)
    {
        $container->setParameter($this->getContainerParamOrServiceId('kernel.reboot'), $kernelConfig['reboot'])
            ->shouldHaveBeenCalledTimes(1);
        $this->assertCreateServiceCalls(
            $container,
            'kernel',
            $kernelConfig['class'],
            array($kernelConfig['env'], $kernelConfig['debug'])
        );
    }

    /**
     * @param ObjectProphecy|ContainerInterface $container
     * @param array                             $loggerConfig
     */
    protected function assertLoadLoggerCalls(ObjectProphecy $container, array $loggerConfig)
    {
        $baseHandlerServiceId = 'logger.handler';
        // Handler
        $this->assertCreateServiceCalls(
            $container,
            $baseHandlerServiceId,
            StreamHandler::class,
            array(
                sprintf(
                    '%s/%s',
                    '%behat.paths.base%',
                    sprintf(
                        '%%%s%%',
                        $loggerConfig['path']
                    )
                ),
                $loggerConfig['level']
            )
        );
        // Logger
        $expectedCallArgumentList = array(
            array(
                'pushHandler',
                array($this->getReferenceAssertion($this->getContainerParamOrServiceId($baseHandlerServiceId)))
            )
        );
        $this->assertCreateServiceCalls(
            $container,
            'logger',
            Logger::class,
            array('behat3Symfony', $loggerConfig['level']),
            array('event_dispatcher.subscriber'),
            $expectedCallArgumentList
        );
        // SfKernelEventLogger
        $this->assertCreateServiceCalls(
            $container,
            'logger.sf_kernel_logger',
            SfKernelEventLogger::class,
            array($this->getReferenceAssertion($this->getContainerParamOrServiceId('kernel'))),
            array('event_dispatcher.subscriber')
        );
    }

    /**
     * @param ObjectProphecy|ContainerInterface $container
     */
    protected function assertLoadHandlerCalls(ObjectProphecy $container)
    {
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

    /**
     * @param ObjectProphecy|ContainerInterface $container
     */
    protected function assertLoadInitializerCalls(ObjectProphecy $container)
    {
        // KernelAware
        $this->assertCreateServiceCalls(
            $container,
            'initializer.kernel_aware',
            KernelHandlerAwareInitializer::class,
            array($this->getReferenceAssertion($this->getContainerParamOrServiceId('handler.kernel'))),
            array('context.initializer')
        );
        // LoggerAware
        $this->assertCreateServiceCalls(
            $container,
            'initializer.logger_aware',
            LoggerAwareInitializer::class,
            array($this->getReferenceAssertion($this->getContainerParamOrServiceId('logger'))),
            array('context.initializer')
        );
        // BehatSubscriber
        $this->assertCreateServiceCalls(
            $container,
            'initializer.behat_subscriber',
            BehatContextSubscriberInitializer::class,
            array($this->getReferenceAssertion('event_dispatcher')),
            array('context.initializer')
        );
    }

    /**
     * @param ObjectProphecy|ContainerInterface $container
     */
    protected function assertLoadSubscriberCalls(ObjectProphecy $container, array $kernelConfig)
    {
        $this->assertCreateServiceCalls(
            $container,
            'subscriber.sf_kernel_logger',
            SfKernelLoggerSubscriber::class,
            array($this->getReferenceAssertion($this->getContainerParamOrServiceId('logger.sf_kernel_logger'))),
            array('event_dispatcher.subscriber')
        );


        if (true === $kernelConfig['reboot']) {
            $this->assertCreateServiceCalls(
                $container,
                'subscriber.reboot_kernel',
                RebootKernelSubscriber::class,
                array($this->getReferenceAssertion($this->getContainerParamOrServiceId('handler.kernel'))),
                array('event_dispatcher.subscriber')
            );
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getContainerParamOrServiceId($key)
    {
        return sprintf(
            '%s.%s',
            Behat3SymfonyExtension::BASE_CONTAINER_ID,
            $key
        );
    }

    /**
     * @param ObjectProphecy|ContainerBuilder $container
     * @param string                          $id
     * @param string                          $class
     * @param array|null                      $expectedDefinitionArgumentList
     * @param array                           $tagList
     * @param array|null                      $expectedCallArgumentList
     * @param bool                            $called
     */
    private function assertCreateServiceCalls(
        ObjectProphecy $container,
        $id,
        $class,
        array $expectedDefinitionArgumentList = null,
        $tagList = array(),
        array $expectedCallArgumentList = null,
        $called = true
    ) {

        $globalSetDefinitionArgumentCheckList = array(
            Argument::type(Definition::class),
            Argument::which('getClass', $class)
        );
        if (null !== $expectedDefinitionArgumentList) {
            $globalSetDefinitionArgumentCheckList[] = Argument::that(
                function (Definition $definition) use ($expectedDefinitionArgumentList) {
                    $argList = $definition->getArguments();
                    foreach ($expectedDefinitionArgumentList as $key => $expected) {
                        $actual = $argList[$key];
                        if ($expected instanceof Token\TokenInterface) {
                            if ($expected->scoreArgument($actual) === false) {
                                return false;
                            }
                        } elseif ($expected != $actual) {
                            return false;
                        }
                    }
                    return true;
                }
            );
        }
        if (null !== $expectedCallArgumentList) {
            $globalSetDefinitionArgumentCheckList[] = Argument::that(
                function (Definition $definition) use ($expectedCallArgumentList) {
                    $callList = $definition->getMethodCalls();
                    foreach ($expectedCallArgumentList as $key => $data) {
                        if ($callList[$key][0] !== $data[0]) {
                            return false;
                        }
                        foreach ($data[1] as $subKey => $expected) {
                            $actual = $callList[$key][1][$subKey];
                            if ($expected instanceof Token\TokenInterface) {
                                if ($expected->scoreArgument($actual) === false) {
                                    return false;
                                }
                            } elseif ($expected != $actual) {
                                return false;
                            }
                        }
                    }
                    return true;
                }
            );
        }

        foreach ($tagList as $tag) {
            // Assert tag is defined
            $globalSetDefinitionArgumentCheckList[] = Argument::that(
                function (Definition $definition) use ($tag) {
                    return $definition->hasTag($tag);
                }
            );
        }

        /** @var MethodProphecy $setDefinitionProphecy */
        $setDefinitionProphecy = $container->setDefinition(
            $this->getContainerParamOrServiceId($id),
            new Token\LogicalAndToken($globalSetDefinitionArgumentCheckList)
        );
        if ($called) {
            $setDefinitionProphecy->shouldHaveBeenCalled();
        } else {
            $setDefinitionProphecy->shouldNotHaveBeenCalled();
        }
    }

    /**
     * @param string $serviceId
     *
     * @return Argument\Token\TokenInterface
     */
    protected function getReferenceAssertion($serviceId)
    {
        return Argument::allOf(
            Argument::type(Reference::class),
            Argument::which('__toString', $serviceId)
        );
    }
}
