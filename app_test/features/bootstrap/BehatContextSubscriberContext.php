<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Yoanm\Behat3SymfonyExtension\Context\BehatContextSubscriberInterface;
use Yoanm\Behat3SymfonyExtension\Event\Events;
use Yoanm\Behat3SymfonyExtension\Event\KernelEvent;

class BehatContextSubscriberContext implements Context, BehatContextSubscriberInterface
{
    /** @var KernelEvent[] */
    private $kernelEventList = [];
    /** @var bool */
    private $listenKernelEvent = false;


    /**
     * @Given I listen for symfony kernel event
     */
    public function iListenForSymfonyKernelEvent()
    {
        $this->listenKernelEvent = true;
        $this->resetKernelEventList();
    }

    /**
     * @Then /^I should have caught (?P<num>\d+) symfony kernel events?$/
     */
    public function IShouldHaveCaughtXSymfonyKernelEvent($num)
    {
        \PHPUnit_Framework_Assert::assertCount((int)$num, $this->kernelEventList);
    }

    /**
     * @Then I should have caught events for symfony kernel shutdown
     */
    public function IShouldHaveCaughtAnEventForSymfonyKernelShutdown()
    {
        \PHPUnit_Framework_Assert::assertSame(
            Events::BEFORE_KERNEL_SHUTDOWN,
            $this->shiftKernelEvent()->getName()
        );
        \PHPUnit_Framework_Assert::assertSame(
            Events::AFTER_KERNEL_SHUTDOWN,
            $this->shiftKernelEvent()->getName()
        );
    }

    /**
     * @Then I should have caught events for symfony kernel boot
     */
    public function IShouldHaveCaughtAnEventForSymfonyKernelBoot()
    {
        \PHPUnit_Framework_Assert::assertSame(
            Events::BEFORE_KERNEL_BOOT,
            $this->shiftKernelEvent()->getName()
        );
        \PHPUnit_Framework_Assert::assertSame(
            Events::AFTER_KERNEL_BOOT,
            $this->shiftKernelEvent()->getName()
        );
    }

    /**
     * @return KernelEvent|null
     */
    protected function shiftKernelEvent()
    {
        return array_shift($this->kernelEventList);
    }

    protected function resetKernelEventList()
    {
        $this->kernelEventList = [];
    }

    public function catchKernelEvent(KernelEvent $event)
    {
        if (true === $this->listenKernelEvent) {
            $this->kernelEventList[] = $event;
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::BEFORE_KERNEL_BOOT => 'catchKernelEvent',
            Events::BEFORE_KERNEL_SHUTDOWN => 'catchKernelEvent',
            Events::AFTER_KERNEL_BOOT => 'catchKernelEvent',
            Events::AFTER_KERNEL_SHUTDOWN => 'catchKernelEvent',
        ];
    }

    /**
     * @BeforeScenario
     */
    public function resetEventList()
    {
        $this->resetKernelEventList();
        $this->listenKernelEvent = false;
    }
}
