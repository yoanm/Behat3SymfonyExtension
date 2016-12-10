<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Behat\Behat\EventDispatcher\Event\BackgroundTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\GherkinNodeTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Tester\Exception\PendingException;
use Yoanm\Behat3SymfonyExtension\Context\BehatContextSubscriberInterface;

class BehatStepLoggerContext implements Context, BehatContextSubscriberInterface
{
    const BEHAT_STEP_LISTENER_SCENARIO_TAG = 'enable-behat-step-listener';

    /** @var GherkinNodeTested[] */
    private $behatStepEvents = [];
    /** @var bool */
    private $listenEvent = false;

    /**
     * @Given I listen for behat steps event
     */
    public function iListenForBehatStepsEvent()
    {
        $this->listenEvent = true;
        $this->resetEventList();
    }

    /**
     * @Given /^I have a log entry regarding current (?P<type>(?:background|feature|scenario(?: outline)?)) start$/
     */
    public function iHaveALogEntryRegardingNodeStart()
    {
        throw new PendingException();
    }

    /**
     * @Then /^I will have a log entry regarding current (?P<type>(?:background|feature|scenario(?: outline)?)) end/
     */
    public function iHaveALogEntryRegardingNodeEnd()
    {
        throw new PendingException();
    }

    /**
     * @Given I have a log entry regarding current step start and end
     */
    public function iHaveALogEntryRegardingCurrentStepStartAndEnd()
    {
        throw new PendingException();
    }

    /**
     * @Given I have a log entry regarding current example start using var :arg1
     */
    public function iWillHaveALogEntryRegardingCurrentExampleStartUsingVar($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then I will have a log entry regarding current example end using var :arg1
     */
    public function iWillHaveALogEntryRegardingCurrentExampleEndUsingVar($arg1)
    {
        throw new PendingException();
    }

    /**
     * @param GherkinNodeTested $event
     */
    public function catchEvent(GherkinNodeTested $event, $name)
    {
        if ($event instanceof BeforeScenarioTested) {
            if(in_array(
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
        if (true === $this->listenEvent) {
            $this->behatStepEvents[] = $event;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FeatureTested::BEFORE => 'catchEvent',
            FeatureTested::AFTER => 'catchEvent',
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
     * @return GherkinNodeTested|null
     */
    protected function shiftEvent()
    {
        return array_shift($this->behatStepEvents);
    }

    protected function resetEventList()
    {
        $this->behatStepEvents = [];
    }
}
