<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Behat\Behat\EventDispatcher\Event\AfterBackgroundTested;
use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BackgroundTested;
use Behat\Behat\EventDispatcher\Event\BeforeBackgroundTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\GherkinNodeTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Testwork\EventDispatcher\Event\AfterTested;
use Yoanm\Behat3SymfonyExtension\Context\BehatContextSubscriberInterface;

class BehatStepLoggerContext implements Context, BehatContextSubscriberInterface
{
    const BEHAT_STEP_LISTENER_SCENARIO_TAG = 'enable-behat-step-listener';

    /** @var GherkinNodeTested[] */
    private $behatStepEvents = [];
    /** @var bool */
    private $listenEvent = false;
    /** @var string */
    private $logFile;

    /**
     * @param string $logFile
     */
    public function __construct($logFile)
    {
        $this->logFile = $logFile;
    }

    /** @var null|ScenarioNode */
    private $currentScenario;
    /** @var null|ExampleNode */
    private $currentExample;
    /** @var null|BackgroundNode */
    private $currentBackground;

    /** @var bool */
    private $expectBackgroundEndEvent = false;
    /** @var bool */
    private $expectScenarioEndEvent = false;
    /** @var bool */
    private $expectStepEndEvent = false;
    /** @var array|bool */
    private $expectExampleEndEventTokenList = false;

    /** @var bool */
    private $expectBackgroundEndEventEntry = false;
    /** @var bool */
    private $expectScenarioEndEventEntry = false;
    /** @var bool */
    private $expectStepEndEventEntry = false;
    /** @var array|bool */
    private $expectExampleEndEventEntryTokenList = false;

    /**
     * @Given I listen for behat steps event
     */
    public function iListenForBehatStepsEvent()
    {
        $this->listenEvent = true;
        $this->resetEventList();
    }

    /**
     * @Given /^I should have caught event regarding current (?P<type>(?:background|scenario)) start event$/
     */
    public function iShouldHaveCaughtEventRegardingNodeStart($type)
    {
        $event = $this->shiftEvent();
        switch ($type) {
            case 'background':
                $eventClass = BeforeBackgroundTested::class;
                break;
            case 'scenario':
                $eventClass = BeforeScenarioTested::class;
                break;

            default:
                throw new \Exception(sprintf('"%s" not handled !', $type));
        }
        \PHPUnit_Framework_Assert::assertInstanceOf(
            $eventClass,
            $event[0],
            sprintf('Failed asserting that start %s event has been received !', $type)
        );
    }

    /**
     * @Then /^I will caught event regarding current (?P<type>(?:background|scenario)) end event$/
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
            default:
                throw new \Exception(sprintf('"%s" not handled !', $type));
        }
    }

    /**
     * @Given I should have caught event regarding current step start event and will have the end event
     */
    public function iShouldHaveCaughtEventRegardingCurrentStepStartAndEnd()
    {
        // Shift an event that is the AfterStepTested event from previous step
        if (false === $this->expectStepEndEvent) {
            $this->shiftEvent();
        }
        \PHPUnit_Framework_Assert::assertInstanceOf(
            BeforeStepTested::class,
            $this->shiftEvent()[0],
            'Failed asserting that start step event has been received !'
        );
        $this->expectStepEndEvent = true;
    }

    /**
     * @Given I should have caught event regarding current example start event using var :arg1
     */
    public function iShouldHaveCaughtEventRegardingCurrentExampleStartUsingVar($arg1)
    {
        \PHPUnit_Framework_Assert::assertInstanceOf(
            BeforeStepTested::class,
            $this->shiftEvent()[0],
            'Failed asserting that start step event has been received !'
        );
    }

    /**
     * @Then I will caught event regarding current example end event using var :arg1
     */
    public function iWillCaughtEventRegardingCurrentExampleEndUsingVar($arg1)
    {
        $this->expectExampleEndEventTokenList = ['var' => $arg1];
    }

    /**
     * @Given /^A log entry must exist for current (?P<type>(?:|background|scenario)) start event$/
     */
    public function aLogEntryMustHaveExistedForCurrentSpecialNodeStartEvent($type)
    {
        $addon = '';
        switch ($type) {
            case 'background':
                $logEntryType = 'BACKGROUND';
                $addon = sprintf(
                    ' {"title":"%s",',
                    $this->currentBackground->getTitle()
                );
                break;
            case 'scenario':
                $logEntryType = 'SCENARIO';
                $addon = sprintf(
                    ' {"title":"%s",',
                    $this->currentScenario->getTitle()
                );
                break;
            default:
                throw new \Exception(sprintf('"%s" not handled !', $type));
        }
        $logEntryType = sprintf(' [%s][IN]%s', $logEntryType, $addon);
        $this->assertLogFileMatch(
            sprintf(
                '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\]%s.*$/m',
                preg_quote($logEntryType, '/')
            ),
            sprintf('Start %s event log entry not found !', $type)
        );
    }

    /**
     * @Then /^I will have a log entry regarding current (?P<type>(?:background|scenario)) end event$/
     */
    public function iWillHaveALogEntryRegardingNodeEndEvent($type)
    {
        switch ($type) {
            case 'background':
                $this->expectBackgroundEndEventEntry = true;
                break;
            case 'scenario':
                $this->expectScenarioEndEventEntry = true;
                break;
                break;
            default:
                throw new \Exception(sprintf('"%s" not handled !', $type));
        }
    }

    /**
     * @Then I will have a log entry regarding current example end event using var :arg1
     */
    public function iWillHaveALogEntryRegardingExampleEndEvent($arg1)
    {
        $this->expectExampleEndEventEntryTokenList = ['var' => $arg1];
    }

    /**
     * @param GherkinNodeTested $event
     */
    public function catchEvent(GherkinNodeTested $event, $name)
    {
        $this->setUpIfNeeded($event);
        if (true === $this->listenEvent) {
            $this->behatStepEvents[] = [$event, $name];
            $this->checkStepEventAssertionIfNeeded($event);
            $this->checkEndEventExpectationIfNeeded($event, $name);
        }
        $this->tearDownIfNeeded($event);
        $this->setCurrentNodeIfNeeded($event);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BackgroundTested::BEFORE => 'catchEvent',
            BackgroundTested::AFTER => 'catchEvent',
            ScenarioTested::BEFORE => 'catchEvent',
            ScenarioTested::AFTER => 'catchEvent',
            OutlineTested::BEFORE => 'catchEvent',
            OutlineTested::AFTER => 'catchEvent',
            ExampleTested::BEFORE => 'catchEvent',
            ExampleTested::AFTER => 'catchEvent',
            StepTested::BEFORE => 'catchEvent',
            StepTested::AFTER => 'catchEvent',
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

    /**
     * @param GherkinNodeTested $event
     */
    private function setCurrentNodeIfNeeded(GherkinNodeTested $event)
    {
        $isAfter = $event instanceof AfterTested;
        if ($event instanceof ScenarioTested) {
            if ($event->getScenario() instanceof ExampleNode) {
                $this->currentExample = $isAfter ? null : $event->getScenario();
            } else {
                $this->currentScenario = $isAfter ? null : $event->getScenario();
            }
        } elseif ($event instanceof BackgroundTested) {
            $this->currentBackground = $isAfter ? null : $event->getBackground();
        }

    }

    /**
     * @param GherkinNodeTested $event
     */
    private function setUpIfNeeded(GherkinNodeTested $event)
    {
        if ($event instanceof BeforeScenarioTested || $event instanceof BeforeOutlineTested) {
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
    }

    /**
     * @param GherkinNodeTested $event
     */
    private function tearDownIfNeeded(GherkinNodeTested $event)
    {
        if ($event instanceof AfterScenarioTested || $event instanceof AfterOutlineTested) {
            $expectBackgroundEndEventBackup = $this->expectBackgroundEndEvent;
            $expectScenarioEndEventBackup = $this->expectScenarioEndEvent;
            $this->expectBackgroundEndEvent = false;
            $this->expectScenarioEndEvent = false;
            \PHPUnit_Framework_Assert::assertFalse(
                $expectBackgroundEndEventBackup,
                'Background end event expected but not catched !'
            );
            \PHPUnit_Framework_Assert::assertFalse(
                $expectScenarioEndEventBackup,
                'Scenario end event expected but not catched !'
            );
        }
    }

    /**
     * @param GherkinNodeTested $event
     * @param string            $name event name
     */
    private function checkEndEventExpectationIfNeeded(GherkinNodeTested $event, $name)
    {
        if ($event instanceof AfterTested) {
            if ($event instanceof AfterBackgroundTested) {
                $this->expectBackgroundEndEvent = false;
                if (true === $this->expectBackgroundEndEventEntry) {
                    $this->assertLogFileMatch(
                        sprintf(
                            '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[BACKGROUND\]\[OUT\] \{"title":"%s",.*$/m',
                            preg_quote($this->currentBackground->getTitle(), '/')
                        ),
                        'Start step event log entry not found !'
                    );
                }
                $this->expectBackgroundEndEventEntry = false;
            }
            if ($event instanceof AfterScenarioTested) {
                $this->expectScenarioEndEvent = false;
                if (true === $this->expectScenarioEndEventEntry) {
                    $this->assertLogFileMatch(
                        sprintf(
                            '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[SCENARIO\]\[OUT\] \{"title":"%s",.*$/m',
                            preg_quote($this->currentScenario->getTitle(), '/')
                        ),
                        'End scenario event log entry not found !'
                    );
                }
                $this->expectScenarioEndEventEntry = false;
            }
            if ($event instanceof AfterScenarioTested && ExampleTested::AFTER === $name) {
                if (is_array($this->expectExampleEndEventTokenList)) {
                    \PHPUnit_Framework_Assert::assertSame(
                        $this->expectExampleEndEventTokenList,
                        $this->currentExample->getTokens(),
                        'End example token are not the expected ones !'
                    );
                }
                $this->expectExampleEndEventTokenList = false;
                if (is_array($this->expectExampleEndEventEntryTokenList)) {
                    $this->assertLogFileMatch(
                        sprintf(
                            '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[SCENARIO EXAMPLE\]\[OUT\] \{"tokens":%s,.*$/m',
                            preg_quote(json_encode($this->currentExample->getTokens()), '/')
                        ),
                        'End example event log entry not found !'
                    );
                }
                $this->expectExampleEndEventEntryTokenList= false;
            }
        }
    }

    /**
     * @param GherkinNodeTested $event
     */
    private function checkStepEventAssertionIfNeeded(GherkinNodeTested $event)
    {
        if ($event instanceof StepTested) {
            if ($event instanceof AfterTested) { // AFTER
                $this->expectStepEndEvent = false;
                if (true === $this->expectStepEndEventEntry) {
                    $this->assertLogFileMatch(
                        '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[STEP\]\[IN\].*$/m',
                        'End step event log entry not found !'
                    );
                }
                $this->expectStepEndEventEntry = false;
            } else { // BEFORE
                // Check if previous step had an expectation on step end event
                $expectStepEndEventBackup = $this->expectStepEndEvent;
                $this->expectStepEndEvent = false;
                \PHPUnit_Framework_Assert::assertFalse(
                    $expectStepEndEventBackup,
                    'Step end event expected but not catched !'
                );
                if (true === $this->expectStepEndEventEntry) {
                    $this->assertLogFileMatch(
                        '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[STEP\]\[OUT\].*$/m',
                        'End step event log entry not found !'
                    );
                }
                $this->expectStepEndEventEntry = false;
            }
        }
    }

    /**
     * @param string $regexp
     */
    protected function assertLogFileMatch($regexp, $message = '')
    {
        \PHPUnit_Framework_Assert::assertRegExp(
            $regexp,
            file_get_contents($this->logFile),
            $message
        );
    }
}
