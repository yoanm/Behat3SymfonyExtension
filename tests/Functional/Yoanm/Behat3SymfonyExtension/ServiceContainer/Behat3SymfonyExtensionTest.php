<?php
namespace Functional\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Functional\Yoanm\BehatUtilsExtension\ServiceContainer\Configuration\ConfigurationTestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Processor;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

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
