<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Event;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Yoanm\Behat3SymfonyExtension\Event\BeforeRequestEvent;

class BeforeRequestEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRequest()
    {
        /** @var Request|ObjectProphecy $request */
        $request = $this->prophesize(Request::class);

        $event = new BeforeRequestEvent($request->reveal());

        $this->assertSame(
            $request->reveal(),
            $event->getRequest()
        );
    }
}
