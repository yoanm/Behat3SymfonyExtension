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
        // Set hight priority to log it at beginning
        $hightPriority = 9999999999999;
        return [
            FeatureTested::BEFORE => ['logEvent', $hightPriority],
            BackgroundTested::BEFORE => ['logEvent', $hightPriority],
            ScenarioTested::BEFORE => ['logEvent', $hightPriority],
            OutlineTested::BEFORE => ['logEvent', $hightPriority],
            ExampleTested::BEFORE => ['logEvent', $hightPriority],
            StepTested::BEFORE => ['logEvent', $hightPriority],
            FeatureTested::AFTER => ['logEvent', $hightPriority],
            BackgroundTested::AFTER => ['logEvent', $hightPriority],
            ScenarioTested::AFTER => ['logEvent', $hightPriority],
            OutlineTested::AFTER => ['logEvent', $hightPriority],
            ExampleTested::AFTER => ['logEvent', $hightPriority],
            StepTested::AFTER => ['logEvent', $hightPriority],
        ];
    }

    /**
     * @param GherkinNodeTested $event
     */
    public function logEvent(GherkinNodeTested $event)
    {
        list($header, $context) = $this->getNodeContext($event);
        $this->logger->debug($header, $context);
    }

    /**
     * @param GherkinNodeTested $event
     * @return array
     */
    protected function getNodeContext(GherkinNodeTested $event)
    {
        list($action, $line) = $this->extractLineAndAction($event);
        list($eventId, $context) = $this->extractTypeAndContext($event, $line);

        return [
            sprintf('[%s][%s]', $eventId, $action),
            $context
        ];
    }

    /**
     * @param GherkinNodeTested $event
     * @return array
     */
    protected function extractLineAndAction(GherkinNodeTested $event)
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
                    return array($action, $line);
                }
                return array($action, $line);
            }
            return array($action, $line);
        }
        return array($action, $line);
    }

    /**
     * @param GherkinNodeTested $event
     * @param int            $line
     *
     * @return array
     */
    protected function extractTypeAndContext(GherkinNodeTested $event, $line)
    {
        $context = [];
        if ($event instanceof StepTested) {
            $eventId = 'STEP';
            $context['text'] = $event->getStep()->getText();
        } elseif ($event instanceof BackgroundTested) {
            $eventId = 'BACKGROUND';
            $context['title'] = $event->getBackground()->getTitle();
        } elseif ($event instanceof ScenarioTested) {
            $scenario = $event->getScenario();
            $eventId = 'SCENARIO';
            if ($scenario instanceof ExampleNode) {
                $eventId .= ' EXAMPLE';
                $context['tokens'] = $scenario->getTokens();
            }
            $context['title'] = $event->getScenario()->getTitle();
        } elseif ($event instanceof OutlineTested) {
            $eventId = 'SCENARIO OUTLINE';
            $context['title'] = $event->getOutline()->getTitle();
        } elseif ($event instanceof FeatureTested) {
            $eventId = 'FEATURE';
            $context['title'] = $event->getFeature()->getTitle();
            $context['file'] = $event->getFeature()->getFile();
        }

        if (!$event instanceof FeatureTested) {
            $context['line'] = $line;
            return array($eventId, $context);
        }
        return array($eventId, $context);
    }
}
