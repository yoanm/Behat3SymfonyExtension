<?php
namespace Technical\Unit\Yoanm\Behat3SymfonyExtension\Client;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Client\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var LoggerInterface|ObjectProphecy */
    private $logger;
    /** @var EventDispatcherInterface|ObjectProphecy */
    private $dispatcher;
    /** @var KernelInterface|ObjectProphecy */
    private $kernel;
    /** @var History|ObjectProphecy */
    private $history;
    /** @var CookieJar|ObjectProphecy */
    private $cookieJar;
    /** @var Client */
    private $client;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->kernel = $this->prophesize(KernelInterface::class);
        $this->history = $this->prophesize(History::class);
        $this->cookieJar = $this->prophesize(CookieJar::class);

        $this->client = new Client(
            $this->kernel->reveal(),
            $this->logger->reveal(),
            $this->dispatcher->reveal(),
            [],
            $this->history->reveal(),
            $this->cookieJar->reveal()
        );
    }

    /**
     * @return boolean
     */
    public function testResetClient()
    {
        $this->logger->debug('Resetting client')->shouldBeCalled();
        $this->kernel->shutdown()->shouldBeCalled();
        $this->kernel->boot()->shouldBeCalled();

        $this->client->resetClient();
    }
}
