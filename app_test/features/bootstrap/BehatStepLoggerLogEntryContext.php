<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Behat\Behat\EventDispatcher\Event\AfterBackgroundTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BackgroundTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
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

class BehatStepLoggerLogEntryContext implements Context, BehatContextSubscriberInterface
{
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
    private $expectScenarioEndEventEntry = false;
    /** @var bool */
    private $expectExampleEndEventEntry = false;
    /** @var bool */
    private $expectBackgroundEndEventEntry = false;
    /** @var bool */
    private $expectStepEndEventEntry = false;

    /**
     * @Given /^A log entry must exist for current (?P<type>(?:|background|scenario|example)) start event$/
     */
    public function aLogEntryMustHaveExistedForCurrentSpecialNodeStartEvent($type)
    {
        if ($type == 'background') {
            $this->assertLogFileMatchBackground(true);
        } elseif ($type == 'scenario') {
            $this->assertLogFileMatchScenario(true);
        } elseif ($type == 'example') {
            $this->assertLogFileMatchExample(true);
        } else {
            throw new \Exception(sprintf('"%s" not handled !', $type));
        }
    }

    /**
     * @Then /^I will have a log entry regarding current (?P<type>(?:background|scenario|example)) end event$/
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
            case 'example':
                $this->expectExampleEndEventEntry = true;
                break;
            default:
                throw new \Exception(sprintf('"%s" not handled !', $type));
        }
    }

    /**
     * @Given A log entry must exist for current step start event and I will have the one regarding end event
     */
    public function aLogEntryMustExistForCurrentStepNodeStartEndEvent()
    {
        $this->assertLogFileMatchStep(true);
        $this->expectStepEndEventEntry = true;
    }

    /**
     * @param BeforeScenarioTested|BeforeOutlineTested $event
     */
    public function setUp(BeforeTested $event, $name)
    {
        if (in_array(
            BehatStepLoggerEventContext::BEHAT_STEP_LISTENER_SCENARIO_TAG,
            array_merge(
                $event->getScenario()->getTags(),
                $event->getFeature()->getTags()
            )
        )) {
            //Auto listen
            $this->listenEvent = true;
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
                if (true === $this->expectStepEndEventEntry) {
                    $this->assertLogFileMatchStep(false);
                }
                $this->expectStepEndEventEntry = false; /* log entry checked so assertion ok */
            } elseif ($event instanceof BackgroundTested) {
                if (true === $this->expectBackgroundEndEventEntry) {
                    $this->assertLogFileMatchBackground(false);
                }
                $this->expectBackgroundEndEventEntry = false; /* log entry checked so assertion ok */
            } else {
                if (ExampleTested::AFTER === $name) {
                    if (true === $this->expectExampleEndEventEntry) {
                        $this->assertLogFileMatchExample(false);
                    }
                    $this->expectExampleEndEventEntry = false; /* log entry checked so assertion ok */
                } else {
                    if (true === $this->expectScenarioEndEventEntry) {
                        $this->assertLogFileMatchScenario(false);
                    }
                    $this->expectScenarioEndEventEntry = false; /* log entry checked so assertion ok */
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
                    $this->expectScenarioEndEventEntry,
                    'Scenario end event entry expected but not checked !'
                );
                \PHPUnit_Framework_Assert::assertFalse(
                    $this->expectExampleEndEventEntry,
                    'Example end event entry expected but not checked !'
                );
            }

            if (
                $event instanceof AfterBackgroundTested
                || $event instanceof AfterScenarioTested
            ) {
                \PHPUnit_Framework_Assert::assertFalse(
                    $this->expectBackgroundEndEventEntry,
                    'Background end event entry expected but not checked !'
                );
            }

            // Following must be always true
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
            ],
            ExampleTested::BEFORE => [
                ['setUp', $hightPriority],
                ['setNodeContext', $hightPriority],
            ],

            BackgroundTested::BEFORE => [
                ['setNodeContext', $hightPriority],
            ],
            StepTested::BEFORE => [
                ['setNodeContext', $hightPriority],
            ],

            StepTested::AFTER => [
                ['checkEndEventExpectation', $hightPriority],
                ['tearDown', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],
            BackgroundTested::AFTER => [
                ['checkEndEventExpectation', $hightPriority],
                ['tearDown', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],

            ScenarioTested::AFTER => [
                ['checkEndEventExpectation', $hightPriority],
                ['tearDown', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],
            ExampleTested::AFTER => [
                ['checkEndEventExpectation', $hightPriority],
                ['tearDown', $hightPriority],
                ['setNodeContext', $lowPriority],
            ],
        ];
    }

    /**
     * @param string     $type
     * @param bool       $isStart
     * @param array|null $extra
     * @param string     $message
     */
    protected function assertLogFileMatch($type, $isStart, array $extra = null, $message = '')
    {
        if (is_array($extra)) {
            $key = array_keys($extra)[0];
            $value = reset($extra);
            $extra = sprintf(
                ' {"%s":%s,',
                $key,
                json_encode($value)
            );
        }
        \PHPUnit_Framework_Assert::assertRegExp(
            sprintf(
                '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[%s\]\[%s\]%s.*$/m',
                $type,
                $isStart ? 'IN' : 'OUT',
                preg_quote($extra,'/')
            ),
            file_get_contents($this->logFile),
            $message
        );
    }

    /**
     * @param bool $isStart
     */
    private function assertLogFileMatchStep($isStart)
    {
        $this->assertLogFileMatch(
            'STEP',
            $isStart,
            ['text' => $this->currentStep->getText()],
            sprintf(
                '%s step event log entry not found !',
                $isStart ? 'Start' : 'End'
            )
        );
    }

    /**
     * @param bool $isStart
     */
    private function assertLogFileMatchBackground($isStart)
    {
        $this->assertLogFileMatch(
            'BACKGROUND',
            $isStart,
            ['title' => $this->currentBackground->getTitle()],
            sprintf(
                '%s background event log entry not found !',
                $isStart ? 'Start' : 'End'
            )
        );
    }

    /**
     * @param bool $isStart
     */
    private function assertLogFileMatchScenario($isStart)
    {
        $this->assertLogFileMatch(
            'SCENARIO',
            $isStart,
            ['title' => $this->currentScenario->getTitle()],
            sprintf(
                '%s scenario event log entry not found !',
                $isStart ? 'Start' : 'End'
            )
        );
    }

    /**
     * @param bool $isStart
     */
    private function assertLogFileMatchExample($isStart)
    {
        $this->assertLogFileMatch(
            'SCENARIO EXAMPLE',
            $isStart,
            ['tokens' => $this->currentScenario->getTokens()],
            sprintf(
                '%s example event log entry not found !',
                $isStart ? 'Start' : 'End'
            )
        );
    }
}
