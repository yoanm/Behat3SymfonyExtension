<?php
namespace Technical\Integration\Yoanm\Behat3SymfonyExtension\Client;

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
use Yoanm\Behat3SymfonyExtension\Event\AfterRequestEvent;
use Yoanm\Behat3SymfonyExtension\Event\BeforeRequestEvent;
use Yoanm\Behat3SymfonyExtension\Event\Events;

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
        $this->history = new History();
        $this->cookieJar = new CookieJar();

        //$this->history->isEmpty()
        $this->client = new Client(
            $this->kernel->reveal(),
            $this->logger->reveal(),
            $this->dispatcher->reveal(),
            [],
            $this->history,
            $this->cookieJar
        );
    }

    public function testKernelNotRebootedAtFirstRequest()
    {
        $this->kernel->shutdown()->shouldNotBeCalled();
        $this->kernel->boot()->shouldNotBeCalled();

        $this->prophesizeKernelRequestHandle();

        $this->client->request('GET', '/');
    }

    public function testEventsAreDispatched()
    {
        $this->dispatcher->dispatch(Events::BEFORE_REQUEST, Argument::type(BeforeRequestEvent::class))
            ->shouldBeCalled();
        $this->dispatcher->dispatch(Events::AFTER_REQUEST, Argument::type(AfterRequestEvent::class))
            ->shouldBeCalled();

        $this->prophesizeKernelRequestHandle();

        $this->client->request('GET', '/');
    }

    public function testKernelIsRebootedAfterFirstRequest()
    {
        $this->prophesizeKernelRequestHandle();

        $this->client->request('GET', '/request_one');

        // Assert that kernel will be rebooted after the second call
        $this->kernel->shutdown()->shouldBeCalledTimes(1);
        $this->kernel->boot()->shouldBeCalledTimes(1);
        $this->logger->debug('A request has already been performed => reboot kernel')
            ->shouldBeCalledTimes(1);
        
        $this->client->request('GET', '/request_two');
    }

    /**
     * @return Response
     */
    protected function prophesizeKernelRequestHandle()
    {
        $this->kernel->handle(Argument::type(Request::class))
            ->willReturn(new Response())
            ->shouldBeCalled();
    }
}
