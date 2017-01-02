<?php
namespace Technical\Unit\Yoanm\Behat3SymfonyExtension\Event;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\Kernel;
use Yoanm\Behat3SymfonyExtension\Event\KernelEvent;

class KernelEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetKernel()
    {
        /** @var Kernel|ObjectProphecy $kernel */
        $kernel = $this->prophesize(Kernel::class);

        $event = new KernelEvent($kernel->reveal());

        $this->assertSame(
            $kernel->reveal(),
            $event->getKernel()
        );
    }

    public function testGetName()
    {
        $name = 'myName';
        /** @var Kernel|ObjectProphecy $kernel */
        $kernel = $this->prophesize(Kernel::class);

        $event = new KernelEvent($kernel->reveal());
        $event->setName($name);
        $this->assertSame(
            $event->getName(),
            $name
        );
    }
}
