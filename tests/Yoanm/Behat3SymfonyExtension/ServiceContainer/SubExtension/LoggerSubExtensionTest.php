<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\AbstractExtensionTest;
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

        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->subExtension->load($container->reveal(), $config);
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
