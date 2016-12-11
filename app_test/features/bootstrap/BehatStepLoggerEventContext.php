<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Behat\Behat\EventDispatcher\Event\AfterBackgroundTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BackgroundTested;
use Behat\Behat\EventDispatcher\Event\BeforeBackgroundTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\GherkinNodeTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Hook\Call\BeforeStep;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\EventDispatcher\Event\AfterTested;
use Behat\Testwork\EventDispatcher\Event\BeforeTested;
use Yoanm\Behat3SymfonyExtension\Context\BehatContextSubscriberInterface;

class BehatStepLoggerEventContext implements Context, BehatContextSubscriberInterface
{
    const BEHAT_STEP_LISTENER_SCENARIO_TAG = 'enable-behat-step-listener';

    /** @var GherkinNodeTested[] */
    private $behatStepEvents = [];
    /** @var bool */
    private $listenEvent = false;

    /** @var null|ScenarioNode|ExampleNode */
    private $currentScenario;
    /** @var null|BackgroundNode */
    private $currentBackground;
    /** @var null|StepNode */
    private $currentStep;

    /** @var bool */
    private $expectScenarioEndEvent = false;
    /** @var bool */
    private $expectExampleEndEvent = false;
    /** @var bool */
    private $expectBackgroundEndEvent = false;
    /** @var bool */
    private $expectStepEndEvent = false;

    /**
     * @Given I listen for behat steps event
     */
    public function iListenForBehatStepsEvent()
    {
        $this->listenEvent = true;
        $this->resetEventList();
    }

    /**
     * @Given /^I should have caught event regarding current (?P<type>(?:background|scenario|example)) start event$/
     */
    public function iShouldHaveCaughtEventRegardingNodeStart($type)
    {
        $eventData = $this->shiftEvent();
        $this->assertStartEventInstanceOf($eventData, $type);
        $this->assertStartEventArgs($eventData, $type);
    }

    /**
     * @Then /^I will caught event regarding current (?P<type>(?:background|scenario|example)) end event$/
     */
    public function iWillCaughtEventRegardingNodeEnd($type)
    {
        switch ($type) {
            case 'background':
                $this->expectBackgroundEndEvent = true;
                break;
            case 'scenario':
                $this->expectScenarioEndEvent = true;
                break;
            case 'example':
                $this->expectExampleEndEvent = true;
                break;
            default:
                throw new \Exception(sprintf('"%s" not handled !', $type));
        }
    }

    /**
     * @Given I should have caught event regarding current step start event and will have the end event
     */
    public function iShouldHaveCaughtEventRegardingCurrentStepStartAndEnd()
    {
        // Shift an event that is the AfterTested event from previous node
        $this->shiftEvent();
        /** @var BeforeStepTested $event */
        $eventData = $this->shiftEvent();
        $this->assertStartEventInstanceOf($eventData, 'step');
        \PHPUnit_Framework_Assert::assertSame(
            'I should have caught event regarding current step start event and will have the end event',
            $eventData[0]->getStep()->getText(),
            'Failed asserting that start step event is the right one !'
        );
        $this->expectStepEndEvent = true;
    }

    /**
     * @param BeforeScenarioTested|BeforeOutlineTested $event
     */
    public function setUp(BeforeTested $event, $name)
    {
        if (in_array(
            self::BEHAT_STEP_LISTENER_SCENARIO_TAG,
            array_merge(
                $event->getScenario()->getTags(),
                $event->getFeature()->getTags()
            )
        )) {
            //Auto listen
            $this->iListenForBehatStepsEvent();
        } else {
            $this->resetEventList();
        }
    }

    /**
     * @param GherkinNodeTested $event
     */
    public function storeEvent(GherkinNodeTested $event, $name)
    {
        if (true === $this->listenEvent) {
            $this->behatStepEvents[] = [$event, $name];
        }
    }

    /**
     * @param GherkinNodeTested $event
     */
    public function setNodeContext(GherkinNodeTested $event)
    {
        if ($this->listenEvent) {
            $isAfter = $event instanceof AfterTested;
            switch (true) {
                case $event instanceof ScenarioTested:
                case $event instanceof ExampleTested:
                    $this->currentScenario = $isAfter ? null : $event->getScenario();
                    break;
                case $event instanceof BackgroundTested:
                    $this->currentBackground = $isAfter ? null : $event->getBackground();
                    break;
                case $event instanceof StepTested:
                    $this->currentStep = $isAfter ? null : $event->getStep();
                    break;
            }
        }
    }

    /**
     * @param GherkinNodeTested $event
     * @param string            $name
     */
    public function checkEndEventExpectation(GherkinNodeTested $event, $name)
    {
        if ($this->listenEvent) {
            if ($event instanceof StepTested) {
                $this->expectStepEndEvent = false; /* event received so assertion ok */
            } elseif ($event instanceof BackgroundTested) {
                $this->expectBackgroundEndEvent = false; /* event received so assertion ok */
            } else {
                if (ExampleTested::AFTER === $name) {
                    if (true === $this->expectExampleEndEvent) {
                        \PHPUnit_Framework_Assert::assertSame(
                            $this->currentScenario->getTokens(),
                            $event->getNode()->getTokens(),
                            'End example tokens are not the expected ones !'
                        );
                    }
                    $this->expectExampleEndEvent = false; /* event received and checked so assertion ok */
                } else {
                    $this->expectScenarioEndEvent = false; /* event received so assertion ok */
                }
            }
        }
    }

    /**
     * @param GherkinNodeTested $event
     */
    public function tearDown(GherkinNodeTested $event)
    {
        if ($this->listenEvent) {
            // Check that all required expectations have been validated
            if ($event instanceof AfterScenarioTested) {
                \PHPUnit_Framework_Assert::assertFalse(
                    $this->expectScenarioEndEvent,
                    'Scenario end event expected but not catched !'
                );
                \PHPUnit_Framework_Assert::assertFalse(
                    $this->expectExampleEndEvent,
                    'Example end event expected but not catched !'
                );
            }

            if (
                $event instanceof AfterBackgroundTested
                || $event instanceof AfterScenarioTested
            ) {
                \PHPUnit_Framework_Assert::assertFalse(
                    $this->expectBackgroundEndEvent,
                    'Background end event expected but not catched !'
                );
            }

            // Following must be always true
            \PHPUnit_Framework_Assert::assertFalse(
                $this->expectStepEndEvent,
                'Step end event expected but not catched !'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        // Set hight priority to have it at beginning
        $hightPriority = 999999999;
        // Set low priority to have it at end
        $lowPriority = $hightPriority*-1;
        return [
            //Set and check at beginning
            ScenarioTested::BEFORE => [
                ['setUp', $hightPriority],
                ['setNodeContext', $hightPriority],
                ['storeEvent', $hightPriority],
            ],
            ExampleTested::BEFORE => [
                ['setUp', $hightPriority],
                ['setNodeContext', $hightPriority],
                ['storeEvent', $hightPriority],
            ],

            BackgroundTested::BEFORE => [
                ['setNodeContext', $hightPriority],
                ['storeEvent', $hightPriority],
            ],
            StepTested::BEFORE => [
                ['setNodeContext', $hightPriority],
                ['storeEvent', $hightPriority],
            ],

            StepTested::AFTER => [
                ['storeEvent', $hightPriority],
                ['checkEndEventExpectation', $hightPriority],
                ['tearDown', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],
            BackgroundTested::AFTER => [
                ['storeEvent', $hightPriority],
                ['checkEndEventExpectation', $hightPriority],
                ['tearDown', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],

            ScenarioTested::AFTER => [
                ['storeEvent', $hightPriority],
                ['checkEndEventExpectation', $hightPriority],
                ['tearDown', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],
            ExampleTested::AFTER => [
                ['storeEvent', $hightPriority],
                ['checkEndEventExpectation', $hightPriority],
                ['tearDown', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],
        ];
    }

    /**
     * @return array|null Event as first value and event name as second value
     */
    protected function shiftEvent()
    {
        return array_shift($this->behatStepEvents);
    }

    protected function resetEventList()
    {
        $this->behatStepEvents = [];
    }

    protected function assertStartEventInstanceOf(array $eventData, $type)
    {
        if ('background' === $type) {
            $className = BeforeBackgroundTested::class;
        } elseif ('example' === $type || 'scenario' === $type) {
            $className = BeforeScenarioTested::class;
        } elseif ('step' === $type) {
            $className = BeforeStepTested::class;
        } else {
            throw new \Exception(sprintf('"%s" not handled !', $type));
        }
        \PHPUnit_Framework_Assert::assertInstanceOf(
            $className,
            $eventData[0],
            sprintf('Failed asserting that start %s event has been received !', $type)
        );
    }

    protected function assertStartEventArgs(array $eventData, $type)
    {
        switch ($type) {
            case 'background':
                $expected = $this->currentBackground->getTitle();
                $current = $eventData[0]->getNode()->getTitle();
                break;
            case 'scenario':
                $expected = $this->currentScenario->getTitle();
                $current = $eventData[0]->getNode()->getTitle();
                break;
            case 'example':
                $expected = $this->currentScenario->getTokens();
                $current = $eventData[0]->getNode()->getTokens();
                break;
            default:
                throw new \Exception(sprintf('"%s" not handled !', $type));
        }

        \PHPUnit_Framework_Assert::assertSame(
            $expected,
            $current,
            sprintf('Failed asserting that start %s event is the right one !', $type)
        );

    }
}
