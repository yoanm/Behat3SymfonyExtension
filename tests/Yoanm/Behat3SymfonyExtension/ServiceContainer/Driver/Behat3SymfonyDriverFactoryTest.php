<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\Driver;

use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Driver\KernelDriver;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Driver\Behat3SymfonyDriverFactory;

class Behat3SymfonyDriverFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var Behat3SymfonyDriverFactory */
    private $factory;

    /**
     * {@inheritDoc}
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

    public function testBuildDriver()
    {
        $definition = $this->factory->buildDriver([]);

        $this->assertSame(
            KernelDriver::class,
            $definition->getClass()
        );

        /** @var Reference $arg0 */
        $arg0 = $definition->getArgument(0);
        $this->assertInstanceOf(
            Reference::class,
            $arg0
        );
        $this->assertSame(
            'behat3_symfony_extension.kernel',
            $arg0->__toString()
        );

        $this->assertSame(
            '%mink.base_url%',
            $definition->getArgument(1)
        );
        $this->assertSame(
            '%behat3_symfony_extension.kernel.reboot%',
            $definition->getArgument(2)
        );
    }
}
