<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

    public function testLoad()
    {
        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->extension->load($container->reveal(), array());
    }
}
