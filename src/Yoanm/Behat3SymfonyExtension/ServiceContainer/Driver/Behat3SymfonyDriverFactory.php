<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\DriverFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Driver\KernelDriver;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

class Behat3SymfonyDriverFactory implements DriverFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'behat3Symfony';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsJavascript()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        if (!class_exists('Behat\Mink\Driver\BrowserKitDriver')) {
            throw new \RuntimeException(
                'Install MinkBrowserKitDriver in order to use the behat3Symfony driver.'
            );
        }

        return new Definition(
            KernelDriver::class,
            array(
                new Reference(Behat3SymfonyExtension::KERNEL_SERVICE_ID),
                '%mink.base_url%',
                sprintf(
                    '%%%s.kernel.reboot%%',
                    Behat3SymfonyExtension::BASE_CONTAINER_ID
                )
            )
        );
    }
}
