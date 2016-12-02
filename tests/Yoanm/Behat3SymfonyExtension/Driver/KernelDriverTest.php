<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Driver;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Yoanm\Behat3SymfonyExtension\Driver\KernelDriver;

/**
 * Class KernelDriverTest
 */
class KernelDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestConstructData
     * @param bool $reboot
     */
    public function testConstruct($reboot)
    {
        /**
         * @var Kernel|ObjectProphecy $kernel
         * @var Client|ObjectProphecy $client
         */
        list($kernel, $client) = $this->prophesizeContruct($reboot);
        new KernelDriver(
            $kernel->reveal(),
            'BASE_URL',
            $reboot
        );

        $client->disableReboot()
            ->shouldHaveBeenCalledTimes($reboot ? 0 : 1);
    }

    public function getTestConstructData()
    {
        return [
            'with reboot' => [
                'reboot' => true,
            ],
            'without reboot' => [
                'reboot' => false,
            ],
        ];
    }

    /**
     * @return [ObjectProphecy]
     */
    protected function prophesizeContruct($reboot = true)
    {
        /** @var Kernel|ObjectProphecy $kernel */
        $kernel = $this->prophesize(Kernel::class);
        /** @var ContainerInterface|ObjectProphecy $container */
        $container = $this->prophesize(ContainerInterface::class);
        /** @var Client|ObjectProphecy $client */
        $client = $this->prophesize(Client::class);

        $kernel->getContainer()
            ->willReturn($container->reveal())
            ->shouldBeCalledTimes(1);
        $container->get('test.client')
            ->willReturn($client->reveal())
            ->shouldBeCalledTimes(1);

        return [$kernel, $client];
    }
}
