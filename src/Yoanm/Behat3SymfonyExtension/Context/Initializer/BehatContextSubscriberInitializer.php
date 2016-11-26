<?php
namespace Yoanm\Behat3SymfonyExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Yoanm\Behat3SymfonyExtension\Context\BehatContextSubscriberInterface;

/**
 * Class BehatContextSubscriberInitializer
 */
class BehatContextSubscriberInitializer implements ContextInitializer
{
    /** @var EventDispatcherInterface */
    private $behatEventDispatcher;

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

        $this->behatEventDispatcher->addSubscriber($context);
    }
}
