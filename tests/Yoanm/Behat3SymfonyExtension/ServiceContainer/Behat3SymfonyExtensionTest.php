<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
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

    public function testConfigure()
    {
        /** @var ArrayNodeDefinition|ObjectProphecy $builder */
        $builder = $this->prophesize(ArrayNodeDefinition::class);
        /** @var NodeBuilder|ObjectProphecy $nodeBuilder */
        $nodeBuilder = $this->prophesize(NodeBuilder::class);
        $builder
            ->addDefaultsIfNotSet()
            ->willReturn($builder->reveal())
            ->shouldBeCalled();
        $builder->children()
            ->willReturn($nodeBuilder->reveal())
            ->shouldBeCalled();
        $nodeBuilder->append(Argument::type(ArrayNodeDefinition::class))
            ->shouldBeCalledTimes(2);

        $nodeBuilder->end()
            ->shouldBeCalled();

        $this->extension->configure($builder->reveal());
    }

    public function testLoad()
    {
        $config = array(
            'kernel' => array(
                'class' => 'class',
                'env' => 'test',
                'debug' => false,
                'reboot' => true,
            ),
            'logger' => array(
                'path' => 'path',
                'level' => 'level'
            ),
        );
        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->assertNull($this->extension->load($container->reveal(), $config));
    }

    public function testProcess()
    {
        /** @var ContainerBuilder|ObjectProphecy $container */
        $container = $this->prophesize(ContainerBuilder::class);

        $this->assertNull($this->extension->process($container->reveal()));
    }
}
