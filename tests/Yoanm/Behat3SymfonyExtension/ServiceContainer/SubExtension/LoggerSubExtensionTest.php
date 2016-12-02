<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\AbstractExtensionTest;
use Yoanm\Behat3SymfonyExtension\Logger\SfKernelEventLogger;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\LoggerSubExtension;

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

    public function testLoad()
    {
        $loggerConfig = array(
            'path' => 'path',
            'level' => 'level',
        );
        $handlerService = 'logger.handler';

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->subExtension->load($container->reveal(), array($this->subExtension->getConfigKey() => $loggerConfig));

        // Handler
        $this->assertCreateServiceCalls(
            $container,
            $handlerService,
            StreamHandler::class,
            array(
                sprintf(
                    '%s/%s',
                    '%behat.paths.base%',
                    $loggerConfig['path']
                ),
                $loggerConfig['level']
            )
        );
        // Logger
        $expectedCallArgumentList = array(
            array(
                'pushHandler',
                array($this->getReferenceAssertion($this->buildContainerId($handlerService)))
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
            array($this->getReferenceAssertion($this->buildContainerId('kernel'))),
            array('event_dispatcher.subscriber')
        );
    }
}
