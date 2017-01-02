<?php
namespace Technical\Unit\Yoanm\Behat3SymfonyExtension\Logger;

use Monolog\Logger;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Yoanm\Behat3SymfonyExtension\Logger\SfKernelEventLogger;

/**
 * Class SfKernelEventLoggerTest
 */
class SfKernelEventLoggerTest extends \PHPUnit_Framework_TestCase
{
    /** @var Logger|ObjectProphecy */
    private $logger;
    /** @var SfKernelEventLogger */
    private $sfKernelLogger;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->logger = $this->prophesize(Logger::class);

        $this->sfKernelLogger = new SfKernelEventLogger(
            $this->logger->reveal()
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [
                KernelEvents::REQUEST => 'onKernelRequest',
                KernelEvents::EXCEPTION => 'onKernelException',
            ],
            SfKernelEventLogger::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider getTestOnKernelRequestData
     *
     * @param string $requestType
     */
    public function testOnKernelRequest($requestType)
    {
        $method = 'METHOD';
        $uri = 'URI';

        /** @var GetResponseEvent|ObjectProphecy $event */
        $event = $this->prophesize(GetResponseEvent::class);
        /** @var Request|ObjectProphecy $request */
        $request = $this->prophesize(Request::class);

        $event->getRequest()
            ->willReturn($request->reveal())
            ->shouldBeCalled();

        $event->getRequestType()
            ->willReturn($requestType)
            ->shouldBeCalled();

        $request->getMethod()
            ->willReturn($method)
            ->shouldBeCalled();

        $request->getUri()
            ->willReturn($uri)
            ->shouldBeCalled();

        $this->logger->debug(
            '[REQUEST]',
            [
                'type' => ($requestType == HttpKernelInterface::MASTER_REQUEST
                    ? 'Master'
                    : 'Sub'
                ),
                'method' => $method,
                'uri' => $uri,
            ]
        );

        $this->sfKernelLogger->onKernelRequest($event->reveal());
    }

    public function testOnKernelException()
    {
        $message = 'MY_MESSAGE';
        /** @var \Exception $exception */
        $exception = new \Exception($message);// Cannot use a mock as getMessage is final :/
        /** @var GetResponseForExceptionEvent|ObjectProphecy $event */
        $event = $this->prophesize(GetResponseForExceptionEvent::class);

        $event->getException()
            ->willReturn($exception)
            ->shouldBeCalled();

        $this->logger
            ->error(
                '[EXCEPTION_THROWN]',
                ['message' => $message]
            )
            ->shouldBeCalled();

        $this->sfKernelLogger->onKernelException($event->reveal());
    }

    public function getTestOnKernelRequestData()
    {
        return [
            'Master' => [
                'requestType' => HttpKernelInterface::MASTER_REQUEST
            ],
            'Other' => [
                'requestType' => HttpKernelInterface::SUB_REQUEST
            ],
        ];
    }
}
