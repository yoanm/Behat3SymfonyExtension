<?php
namespace Technical\Unit\Yoanm\Behat3SymfonyExtension\ServiceContainer\DriverFactory;

use Behat\Mink\Driver\BrowserKitDriver;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\DriverFactory\Behat3SymfonyDriverFactory;

class Behat3SymfonyDriverFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var Behat3SymfonyDriverFactory */
    private $factory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->factory = new Behat3SymfonyDriverFactory();
    }

    public function testGetDriverName()
    {
        $this->assertSame(
            'behat3Symfony',
            $this->factory->getDriverName()
        );
    }

    public function testSupportsJavascript()
    {
        $this->assertSame(
            false,
            $this->factory->supportsJavascript()
        );
    }

    public function testConfigure()
    {
        /** @var ArrayNodeDefinition|ObjectProphecy $arrayNodeDefinition */
        $arrayNodeDefinition = $this->prophesize(ArrayNodeDefinition::class);
        $this->assertNull(
            $this->factory->configure($arrayNodeDefinition->reveal())
        );
    }

    public function testBuildDriver()
    {
        $definition = $this->factory->buildDriver([]);

        $this->assertSame(
            BrowserKitDriver::class,
            $definition->getClass()
        );

        /** @var Reference $arg0 */
        $arg0 = $definition->getArgument(0);
        $this->assertInstanceOf(
            Reference::class,
            $arg0
        );
        $this->assertSame(
            Behat3SymfonyExtension::TEST_CLIENT_SERVICE_ID,
            $arg0->__toString()
        );

        $this->assertSame(
            '%mink.base_url%',
            $definition->getArgument(1)
        );
    }
}
