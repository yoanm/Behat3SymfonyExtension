<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\AbstractExtensionTest;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\LoggerAwareInitializer;
use Yoanm\Behat3SymfonyExtension\Logger\SfKernelEventLogger;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\LoggerSubExtension;
use Yoanm\Behat3SymfonyExtension\Subscriber\SfKernelLoggerSubscriber;

class LoggerSubExtensionTest extends AbstractExtensionTest
{
    /** @var LoggerSubExtension*/
    private $subExtension;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->subExtension = new LoggerSubExtension();
    }

    public function testGetConfigKey()
    {
        $this->assertSame(
            'logger',
            $this->subExtension->getConfigKey()
        );
    }

    /**
     * @dataProvider getTestLoadData
     *
     * @param bool $debug
     */
    public function testLoad($debug)
    {
        $config = [
            'kernel' => [
                'debug' => $debug,
            ],
            'logger' => [
                'path' => __DIR__.'../LoggerSubExtensionTest.php',
                'level' => 'level',
            ],
        ];
        $handlerService = 'logger.handler';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->subExtension->load($container->reveal(), $config);

        // Handler
        $this->assertCreateServiceCalls(
            $container,
            $handlerService,
            StreamHandler::class,
            [
                $config['logger']['path'],
                $config['logger']['level'],
            ]
        );
        // Logger
        $expectedCallArgumentList = [
            [
                'pushHandler',
                [$this->getReferenceAssertion($this->buildContainerId($handlerService))]
            ]
        ];
        $this->assertCreateServiceCalls(
            $container,
            'logger',
            Logger::class,
            ['behat3Symfony'],
            [],
            $expectedCallArgumentList
        );
        // SfKernelEventLogger
        $this->assertCreateServiceCalls(
            $container,
            'subscriber.sf_kernel_logger',
            SfKernelLoggerSubscriber::class,
            [$this->getReferenceAssertion($this->buildContainerId('logger.sf_kernel_logger'))],
            [],
            null,
            true === $debug
        );
        $this->assertCreateServiceCalls(
            $container,
            'logger.sf_kernel_logger',
            SfKernelEventLogger::class,
            [$this->getReferenceAssertion($this->buildContainerId('logger'))],
            [],
            null,
            true === $debug
        );
        // LoggerAware
        $this->assertCreateServiceCalls(
            $container,
            'initializer.logger_aware',
            LoggerAwareInitializer::class,
            [$this->getReferenceAssertion($this->buildContainerId('logger'))],
            ['context.initializer']
        );
    }

    public function testProcess()
    {
        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->assertNull($this->subExtension->process($container->reveal()));
    }

    /**
     * @return array
     */
    public function getTestLoadData()
    {
        return [
            'debug mode' => [
                'debug' => true,
            ],
            'not debug mode' => [
                'debug' => false,
            ],
        ];
    }
}
