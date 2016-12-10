<?php
namespace Yoanm\Behat3SymfonyExtension\Subscriber;

use Behat\Behat\EventDispatcher\Event\BackgroundTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\GherkinNodeTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\StepContainerInterface;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\EventDispatcher\Event\AfterTested;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class BehatStepLoggerSubscriber
 */
class BehatStepLoggerSubscriber implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FeatureTested::BEFORE => 'featureEvents',
            FeatureTested::AFTER => 'featureEvents',
            BackgroundTested::BEFORE => 'backgroundEvents',
            BackgroundTested::AFTER => 'backgroundEvents',
            ScenarioTested::BEFORE => 'scenarioEvents',
            ScenarioTested::AFTER => 'scenarioEvents',
            OutlineTested::BEFORE => 'outlineEvents',
            OutlineTested::AFTER => 'outlineEvents',
            ExampleTested::BEFORE => 'exampleEvents',
            ExampleTested::AFTER => 'exampleEvents',
            StepTested::BEFORE => 'stepEvents',
            StepTested::AFTER => 'stepEvents',
        ];
    }

    /**
     * @param FeatureTested $event
     */
    public function featureEvents(FeatureTested $event)
    {
        list($header,) = $this->getNodeContext('FEATURE', $event);
        $this->logger->debug(
            $header,
            [
                'title' => $event->getFeature()->getTitle(),
                'file' => $event->getFeature()->getFile(),
            ]
        );
    }

    /**
     * @param BackgroundTested $event
     */
    public function backgroundEvents(BackgroundTested $event)
    {
        list($header, $line) = $this->getNodeContext('BACKGROUND', $event);
        $this->logger->debug(
            $header,
            [
                'title' => $event->getBackground()->getTitle(),
                'line' => $line,
            ]
        );
    }

    /**
     * @param ScenarioTested $event
     */
    public function scenarioEvents(ScenarioTested $event)
    {
        list($header, $line) = $this->getNodeContext('SCENARIO', $event);
        $this->logger->debug(
            $header,
            [
                'title' => $event->getScenario()->getTitle(),
                'line' => $line,
            ]
        );
    }

    /**
     * @param OutlineTested $event
     */
    public function outlineEvents(OutlineTested $event)
    {
        list($header, $line) = $this->getNodeContext('SCENARIO OUTLINE', $event);
        $this->logger->debug(
            $header,
            [
                'title' => $event->getOutline()->getTitle(),
                'line' => $line,
            ]
        );
    }

    /**
     * @param ScenarioTested $event
     */
    public function exampleEvents(ScenarioTested $event)
    {
        list($header, $line) = $this->getNodeContext('SCENARIO EXAMPLE', $event);
        $tokens = [];
        $scenario = $event->getScenario();
        if ($scenario instanceof ExampleNode) {
            $tokens = $scenario->getTokens();
        }
        $this->logger->debug(
            $header,
            [
                'tokens' => $tokens,
                'line' => $line,
            ]
        );
    }

    /**
     * @param StepTested $event
     */
    public function stepEvents(StepTested $event)
    {
        list($header, $line) = $this->getNodeContext('STEP', $event);
        $this->logger->debug(
            $header,
            [
                'text' => $event->getStep()->getText(),
                'line' => $line,
            ]
        );
    }

    /**
     * @param string            $eventId
     * @param GherkinNodeTested $event
     *
     * @return array the action text as first value and the node start line as second value
     */
    protected function getNodeContext($eventId, GherkinNodeTested $event)
    {
        $action = 'IN';
        $line = $event->getNode()->getLine();
        if ($event instanceof AfterTested) {
            $action = 'OUT';
            $node = $event->getNode();
            if ($node instanceof StepContainerInterface) {
                $stepList = $node->getSteps();
                $lastStep = array_pop($stepList);
                // Check if StepContainer is not empty
                if ($lastStep instanceof StepNode) {
                    $line = $lastStep->getLine();
                }
            }
        }
        $header = sprintf('[%s][%s]', $eventId, $action);

        return [$header, $line];
    }
}
