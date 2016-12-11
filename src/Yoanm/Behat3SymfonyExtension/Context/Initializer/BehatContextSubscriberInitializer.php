<?php
namespace Yoanm\Behat3SymfonyExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Yoanm\Behat3SymfonyExtension\Context\BehatContextSubscriberInterface;

/**
 * Class BehatContextSubscriberInitializer
 * /!\ /!\ Contexts will be aware of behat event only from ScenarioTested::BEFORE to ScenarioTested::AFTER only /!\ /!\
 *
 * This is due to the fact that context are (re)-created for each scenario or example
 * So contexts will have access to the followings event workflow (depending of the type of the scenario) :
 *
 * - Basic scenario :
 *      - ScenarioTested
 *          - BEFORE
 *          - AFTER_SETUP
 *      - BackgroundTested
 *          - BEFORE
 *          - AFTER_SETUP
 *      - StepTested
 *          - BEFORE
 *          - AFTER_SETUP
 *          - BEFORE_TEARDOWN
 *          - AFTER
 *      - BackgroundTested
 *          - BEFORE_TEARDOWN
 *          - AFTER
 *      - ScenarioTested
 *          - BEFORE_TEARDOWN
 *          - AFTER
 *
 * - Scenario outline :
 *      - ExampleTested
 *          - BEFORE
 *          - AFTER_SETUP
 *      - BackgroundTested
 *          - BEFORE
 *          - AFTER_SETUP
 *      - StepTested
 *          - BEFORE
 *          - AFTER_SETUP
 *          - BEFORE_TEARDOWN
 *          - AFTER
 *      - BackgroundTested
 *          - BEFORE_TEARDOWN
 *          - AFTER
 *      - ExampleTested
 *          - BEFORE_TEARDOWN
 *          - AFTER
 */
class BehatContextSubscriberInitializer implements ContextInitializer, EventSubscriberInterface
{
    /** @var EventDispatcherInterface */
    private $behatEventDispatcher;
    /** @var BehatContextSubscriberInterface[] */
    private $registeredContextList = [];


    /**
     * @param EventDispatcherInterface $behatEventDispatcher
     */
    public function __construct(EventDispatcherInterface $behatEventDispatcher)
    {
        $this->behatEventDispatcher = $behatEventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        if (!$context instanceof BehatContextSubscriberInterface) {
            return;
        }
        // This method is called before each scenario/example, so context is probably already registered
        // To avoid any problem, keep a trace of registered context and remove it at feature end
        // (See clearBehatContextSubscriber method)
        $this->registeredContextList[] = $context;
        $this->behatEventDispatcher->addSubscriber($context);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScenarioTested::AFTER => 'clearBehatContextSubscriber',
            ExampleTested::AFTER => 'clearBehatContextSubscriber',
        ];
    }

    /**
     * Clear contexts subscriber after each scenario/example
     */
    public function clearBehatContextSubscriber()
    {
        foreach ($this->registeredContextList as $context) {
            $this->behatEventDispatcher->removeSubscriber($context);
        }
        $this->registeredContextList = [];
    }
}
