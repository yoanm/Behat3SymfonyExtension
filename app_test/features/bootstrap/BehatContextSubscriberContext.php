<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Yoanm\Behat3SymfonyExtension\Context\BehatContextSubscriberInterface;
use Yoanm\Behat3SymfonyExtension\Event\AbstractEvent;
use Yoanm\Behat3SymfonyExtension\Event\Events;

class BehatContextSubscriberContext implements Context, BehatContextSubscriberInterface
{
    /** @var AbstractEvent[] */
    private $kernelEventList = [];
    /** @var bool */
    private $listenEvent = false;

    /**
     * @BeforeScenario
     */
    public function beforeScenario()
    {
        $this->resetEventList();
        $this->listenEvent = false;
    }


    /**
     * @Given I listen for symfony kernel event
     */
    public function iListenForSymfonyEvent()
    {
        $this->listenEvent = true;
        $this->resetEventList();
    }

    /**
     * @Then /^I should have caught (?P<num>\d+) symfony kernel events?$/
     */
    public function IShouldHaveCaughtXSymfonyEvent($num)
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
            $this->shiftEvent()->getName()
        );
        \PHPUnit_Framework_Assert::assertSame(
            Events::AFTER_KERNEL_SHUTDOWN,
            $this->shiftEvent()->getName()
        );
    }

    /**
     * @Then /^I should have caught events for client request(?P<beforeOnly>, before event only)?$/
     */
    public function IShouldHaveCaughtAnEventForClientRequest($beforeOnly = false)
    {
        var_dump($beforeOnly);
        $beforeOnly = $beforeOnly ? '' !== trim($beforeOnly) : false;
        var_dump($beforeOnly);
        \PHPUnit_Framework_Assert::assertSame(
            Events::BEFORE_REQUEST,
            $this->shiftEvent()->getName()
        );
        if (false === $beforeOnly) {
            \PHPUnit_Framework_Assert::assertSame(
                Events::AFTER_REQUEST,
                $this->shiftEvent()->getName()
            );
        }
    }

    /**
     * @Then I should have caught events for symfony kernel boot
     */
    public function IShouldHaveCaughtAnEventForSymfonyKernelBoot()
    {
        \PHPUnit_Framework_Assert::assertSame(
            Events::BEFORE_KERNEL_BOOT,
            $this->shiftEvent()->getName()
        );
        \PHPUnit_Framework_Assert::assertSame(
            Events::AFTER_KERNEL_BOOT,
            $this->shiftEvent()->getName()
        );
    }

    /**
     * @param AbstractEvent $event
     */
    public function catchEvent(AbstractEvent $event)
    {
        if (true === $this->listenEvent) {
            $this->kernelEventList[] = $event;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::BEFORE_KERNEL_BOOT => 'catchEvent',
            Events::BEFORE_KERNEL_SHUTDOWN => 'catchEvent',
            Events::BEFORE_REQUEST => 'catchEvent',
            Events::AFTER_KERNEL_BOOT => 'catchEvent',
            Events::AFTER_KERNEL_SHUTDOWN => 'catchEvent',
            Events::AFTER_REQUEST => 'catchEvent',
        ];
    }

    /**
     * @return AbstractEvent|null
     */
    protected function shiftEvent()
    {
        return array_shift($this->kernelEventList);
    }

    protected function resetEventList()
    {
        $this->kernelEventList = [];
    }
}
