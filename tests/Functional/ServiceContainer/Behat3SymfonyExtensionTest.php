<?php
namespace Functional\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Functional\Yoanm\BehatUtilsExtension\ServiceContainer\Configuration\ConfigurationTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Processor;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\DriverFactory\Behat3SymfonyDriverFactory;

class Behat3SymfonyExtensionTest extends ConfigurationTestCase
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

    public function testDefaultConfiguration()
    {
        $config = $this->processConfiguration();

        $this->assertFalse($config['debug_mode']);
    }

    public function testDriverFactoryAdded()
    {
        /** @var MinkExtension|ObjectProphecy $minkExtension */
        $minkExtension = $this->prophesize(MinkExtension::class);
        $minkExtension->getConfigKey()->willReturn('mink');
        $minkExtension->registerDriverFactory(Argument::type(Behat3SymfonyDriverFactory::class))
            ->shouldBeCalled();
        $extensionManager = new ExtensionManager([$minkExtension->reveal()]);

        $this->extension->initialize($extensionManager);
    }

    /**
     * @param array $configs
     * @return array
     */
    protected function processConfiguration(array $configs = [])
    {
        $builder = new ArrayNodeDefinition('test');

        $this->extension->configure($builder);

        return (new Processor())->process(
            $builder->getNode(true),
            $configs
        );
    }
}
