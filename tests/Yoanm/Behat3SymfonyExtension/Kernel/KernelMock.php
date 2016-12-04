<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Kernel;

use Symfony\Component\Config\Loader\LoaderInterface;
use Yoanm\Behat3SymfonyExtension\Kernel\Kernel;

class KernelMock extends Kernel
{
    /**
     * @inheritDoc
     */
    public function registerBundles()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        // TODO: Implement registerContainerConfiguration() method.
    }

}
