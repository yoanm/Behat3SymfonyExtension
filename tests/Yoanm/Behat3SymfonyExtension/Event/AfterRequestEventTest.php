<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Event;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Response;
use Yoanm\Behat3SymfonyExtension\Event\AfterRequestEvent;

class AfterResponseEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetResponse()
    {
        /** @var Response|ObjectProphecy $response */
        $response = $this->prophesize(Response::class);

        $event = new AfterRequestEvent($response->reveal());

        $this->assertSame(
            $response->reveal(),
            $event->getResponse()
        );
    }
}
