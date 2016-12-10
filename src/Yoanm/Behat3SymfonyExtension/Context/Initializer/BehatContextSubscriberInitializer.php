<?php
namespace Yoanm\Behat3SymfonyExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Yoanm\Behat3SymfonyExtension\Context\BehatContextSubscriberInterface;

/**
 * Class BehatContextSubscriberInitializer
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
        return [FeatureTested::AFTER => 'clearBehatContextSubscriber'];
    }

    /**
     * Clear contexts subscriber after each feature
     */
    public function clearBehatContextSubscriber()
    {
        foreach ($this->registeredContextList as $context) {
            $this->behatEventDispatcher->removeSubscriber($context);
        }
        $this->registeredContextList = [];
    }
}
