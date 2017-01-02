<?php
namespace Functional\Yoanm\Behat3SymfonyExtension\ServiceContainer\Configuration;

use Functional\Yoanm\BehatUtilsExtension\ServiceContainer\Configuration\ConfigurationTestCase;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Configuration\KernelConfiguration;

class KernelConfigurationTest extends ConfigurationTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return new KernelConfiguration();
    }

    public function testDefaultConfiguration()
    {
        $config = $this->processConfiguration();

        $this->assertSame('app/autoload.php', $config['bootstrap']);
        $this->assertSame('app/AppKernel.php', $config['path']);
        $this->assertSame('AppKernel', $config['class']);
        $this->assertSame('test', $config['env']);
        $this->assertSame(true, $config['debug']);
        $this->assertSame(true, $config['reboot']);
    }
}
