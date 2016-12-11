<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Behat\Behat\EventDispatcher\Event\AfterBackgroundTested;
use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BackgroundTested;
use Behat\Behat\EventDispatcher\Event\BeforeBackgroundTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\GherkinNodeTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\EventDispatcher\Event\AfterTested;
use Behat\Testwork\EventDispatcher\Event\BeforeTested;
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

    /** @var null|ScenarioNode|ExampleNode */
    private $currentScenario;
    /** @var null|BackgroundNode */
    private $currentBackground;
    /** @var null|StepNode */
    private $currentStep;

    /** @var bool */
    private $expectScenarioEndEvent = false;
    /** @var bool */
    private $expectScenarioEndEventEntry = false;
    /** @var array|bool */
    private $expectExampleEndEventTokenList = false;
    /** @var array|bool */
    private $expectExampleEndEventEntryTokenList = false;
    /** @var bool */
    private $expectBackgroundEndEvent = false;
    /** @var bool */
    private $expectBackgroundEndEventEntry = false;
    /** @var bool */
    private $expectStepEndEvent = false;
    /** @var bool */
    private $expectStepEndEventEntry = false;

    /**
     * @Given I listen for behat steps event
     */
    public function iListenForBehatStepsEvent()
    {
        $this->listenEvent = true;
        $this->resetEventList();
    }

    /** START EVENT */

        /** BACKGROUND/SCENARIO */

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

        \PHPUnit_Framework_Assert::assertSame(
            $type == 'background'
                ? $this->currentBackground->getTitle()
                : $this->currentScenario->getTitle(),
            $event[0]->getNode()->getTitle(),
            sprintf('Failed asserting that start %s event is the right one !', $type)
        );
    }

    /**
     * @Given /^A log entry must exist for current (?P<type>(?:|background|scenario)) start event$/
     */
    public function aLogEntryMustHaveExistedForCurrentSpecialNodeStartEvent($type)
    {
        switch ($type) {
            case 'background':
                $logEntryType = 'BACKGROUND';
                $nodeTitle = $this->currentBackground->getTitle();
                break;
            case 'scenario':
                $logEntryType = 'SCENARIO';
                $nodeTitle = $this->currentScenario->getTitle();
                break;
            default:
                throw new \Exception(sprintf('"%s" not handled !', $type));
        }
        $this->assertLogFileMatch(
            sprintf(
                '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\]%s.*$/m',
                preg_quote(
                    sprintf(
                        ' [%s][IN] {"title":"%s",',
                        $logEntryType,
                        $nodeTitle
                    ),
                    '/'
                )
            ),
            sprintf('Start %s event log entry not found !', $type)
        );
    }
        /** END - BACKGROUND/SCENARIO */

        /** EXAMPLE */

    /**
     * @Given I should have caught event regarding current example start event using var :arg1
     */
    public function iShouldHaveCaughtEventRegardingCurrentExampleStartUsingVar($arg1)
    {
        /** @var BeforeStepTested $event */
        $event = $this->shiftEvent()[0];
        \PHPUnit_Framework_Assert::assertInstanceOf(
            BeforeStepTested::class,
            $event,
            'Failed asserting that start example event has been received !'
        );
        /** @var ExampleNode $node */
        $node = $event->getNode();
        \PHPUnit_Framework_Assert::assertInstanceOf(
            ExampleNode::class,
            'Failed asserting that current node is an example !'
        );
        \PHPUnit_Framework_Assert::assertSame(
            ['var' => $arg1],
            $node->getTokens(),
            'Start example event tokens mismatch !'
        );
    }

    /**
     * @Given A log entry must exist for current example start event using var :arg1
     */
    public function aLogEntryMustExistForCurrentExampleStartEvent($arg1)
    {
        $this->assertLogFileMatch(
            sprintf(
                '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[SCENARIO EXAMPLE\]\[IN\] \{"tokens":%s.*$/m',
                preg_quote(json_encode(['var' => $arg1]), '/')
            ),
            'Start example event log entry not found !'
        );
    }

        /** END - EXAMPLE */

    /** END - START EVENT */

    /** END EVENT */

        /** BACKGROUND/SCENARIO */

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
        /** END - BACKGROUND/SCENARIO */

        /** EXAMPLE */

    /**
     * @Then I will caught event regarding current example end event using var :arg1
     */
    public function iWillCaughtEventRegardingCurrentExampleEndUsingVar($arg1)
    {
        $this->expectExampleEndEventTokenList = ['var' => $arg1];
    }
    /**
     * @Then I will have a log entry regarding current example end event using var :arg1
     */
    public function iWillHaveALogEntryRegardingExampleEndEvent($arg1)
    {
        $this->expectExampleEndEventEntryTokenList = ['var' => $arg1];
    }
        /** END - EXAMPLE */

    /** END - END EVENT */

    /** STEP */

    /**
     * @Given I should have caught event regarding current step start event and will have the end event
     */
    public function iShouldHaveCaughtEventRegardingCurrentStepStartAndEnd()
    {
        // Shift an event that is the AfterTested event from previous node
        $this->shiftEvent();
        /** @var BeforeStepTested $event */
        $event = $this->shiftEvent()[0];
        \PHPUnit_Framework_Assert::assertInstanceOf(
            BeforeStepTested::class,
            $event,
            'Failed asserting that start step event has been received !'
        );
        \PHPUnit_Framework_Assert::assertSame(
            'I should have caught event regarding current step start event and will have the end event',
            $event->getStep()->getText(),
            'Failed asserting that start step event is the right one !'
        );
        $this->expectStepEndEvent = true;
    }

    /**
     * @Given A log entry must exist for current step start event and I will have the one regarding end event
     */
    public function aLogEntryMustExistForCurrentStepNodeStartEndEvent()
    {
        $this->assertLogFileMatch(
            sprintf(
                '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[STEP\]\[IN\] \{"text":"%s".*$/m',
                preg_quote(
                    'A log entry must exist for current step start event and I will have the one regarding end event',
                    '/'
                )
            ),
            'Start step event log entry not found !'
        );
        $this->expectStepEndEventEntry = true;
    }

    /** END - STEP */

    /** CATCH EVENT BEHAVIOR */

    /**
     * @param BeforeScenarioTested|BeforeOutlineTested $event
     */
    public function setUp(BeforeTested $event, $name)
    {
        //var_dump("SET UP(".get_class($event).'{'.$name.'})');
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
            //var_dump("storeEvent(".get_class($event)." {$name}) => ".($event instanceof AfterTested ? 'END' : 'START'));
            $this->behatStepEvents[] = [$event, $name];
        }
    }

    /**
     * @param GherkinNodeTested $event
     */
    public function setNodeContext(GherkinNodeTested $event)
    {
        if ($this->listenEvent) {
            ////var_dump("setNodeContext(" . get_class($event) . ') => ' . ($event instanceof AfterTested ? 'END' : 'START'));
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

    public function tearDownBackground($event, $name)
    {
        if ($this->listenEvent) {
            //var_dump("TEAR DOWN BACKGROUND(".get_class($event).'{'.$name.'})');
            // Event received, assertion OK
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

            // Check that required expectation have been validated
            \PHPUnit_Framework_Assert::assertFalse(
                $this->expectStepEndEvent,
                'Step end event expected but not catched !'
            );
        }
    }

    public function tearDownStep($event, $name)
    {
        if ($this->listenEvent) {
            ////var_dump("TEAR DOWN STEP(".get_class($event).')');

            // Event received, assertion OK
            $this->expectStepEndEvent = false;

            // If current step have an expectation on step end log entry => check it
            if (true === $this->expectStepEndEventEntry) {
                $this->assertLogFileMatch(
                    sprintf(
                        '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[STEP\]\[OUT\] \{"text":"%s".*$/m',
                        preg_quote(
                            str_replace('""', '\"', $this->currentStep->getText()),
                            '/'
                        )
                    ),
                    'Step end log entry not found !'
                );
            }
            $this->expectStepEndEventEntry = false;
        }
    }

    public function tearDown($event, $name)
    {
        if ($this->listenEvent) {
            //var_dump("TEAR DOWN(".get_class($event).'{'.$name.'})');
            if (ExampleTested::AFTER === $name) {
                if (is_array($this->expectExampleEndEventTokenList)) {
                    \PHPUnit_Framework_Assert::assertSame(
                        $this->expectExampleEndEventTokenList,
                        $this->currentScenario->getTokens(),
                        'End example tokens are not the expected ones !'
                    );
                }
                // Event received and checked, assertion OK
                $this->expectExampleEndEventTokenList = false;
                if (is_array($this->expectExampleEndEventEntryTokenList)) {
                    $this->assertLogFileMatch(
                        sprintf(
                            '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[SCENARIO EXAMPLE\]\[OUT\] \{"tokens":%s,.*$/m',
                            preg_quote(json_encode($this->currentScenario->getTokens()), '/')
                        ),
                        'End example event log entry not found !'
                    );
                }
                $this->expectExampleEndEventEntryTokenList = false;
            } else {
                // Event received, assertion OK
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
        }
    }

    public function checkEndEventExpectation($event, $name)
    {
        if ($this->listenEvent) {
            //var_dump("checkEndEventExpectation(".get_class($event).'{'.$name.'})');
            if ($event instanceof AfterScenarioTested) {
                // Check that all required expectations have been validated
                \PHPUnit_Framework_Assert::assertFalse(
                    $this->expectScenarioEndEvent,
                    'Scenario end event expected but not catched !'
                );
                \PHPUnit_Framework_Assert::assertFalse(
                    $this->expectScenarioEndEventEntry,
                    'Scenario end event entry expected but not checked !'
                );
                \PHPUnit_Framework_Assert::assertFalse(
                    $this->expectExampleEndEventTokenList,
                    'Example end event expected but not catched !'
                );
                \PHPUnit_Framework_Assert::assertFalse(
                    $this->expectExampleEndEventEntryTokenList,
                    'Example end event entry expected but not checked !'
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
                \PHPUnit_Framework_Assert::assertFalse(
                    $this->expectBackgroundEndEventEntry,
                    'Background end event entry expected but not checked !'
                );
            }

            // Following must be always true
            \PHPUnit_Framework_Assert::assertFalse(
                $this->expectStepEndEvent,
                'Step end event expected but not catched !'
            );
            \PHPUnit_Framework_Assert::assertFalse(
                $this->expectStepEndEventEntry,
                'Step end event entry expected but not checked !'
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
                ['tearDownStep', $hightPriority],
                ['checkEndEventExpectation', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],
            BackgroundTested::AFTER => [
                ['storeEvent', $hightPriority],
                ['tearDownBackground', $hightPriority],
                ['checkEndEventExpectation', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],

            ScenarioTested::AFTER => [
                ['storeEvent', $hightPriority],
                ['tearDown', $hightPriority],
                ['checkEndEventExpectation', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],
            ExampleTested::AFTER => [
                ['storeEvent', $hightPriority],
                ['tearDown', $hightPriority],
                ['checkEndEventExpectation', $hightPriority],
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

    /** END - CATCH EVENT BEHAVIOR */

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
