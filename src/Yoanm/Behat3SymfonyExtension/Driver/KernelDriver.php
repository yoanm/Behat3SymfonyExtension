<?php
namespace Yoanm\Behat3SymfonyExtension\Driver;

use Behat\Mink\Driver\BrowserKitDriver;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class KernelDriver
 */
class KernelDriver extends BrowserKitDriver
{
    /**
     * @param Kernel $kernel
     * @param string $baseUrl
     * @param bool   $allowClientReboot
     */
    public function __construct(Kernel $kernel, $baseUrl, $allowClientReboot = true)
    {
        /** @var Client $client */
        $client = $kernel->getContainer()->get('test.client');

        if (false === $allowClientReboot) {
            $client->disableReboot();
        }
        parent::__construct($client, $baseUrl);
    }
}
