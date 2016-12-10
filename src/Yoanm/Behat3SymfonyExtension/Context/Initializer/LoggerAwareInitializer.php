<?php
namespace Yoanm\Behat3SymfonyExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Psr\Log\LoggerInterface;
use Yoanm\Behat3SymfonyExtension\Context\LoggerAwareInterface;

/**
 * Class LoggerAwareInitializer
 */
class LoggerAwareInitializer implements ContextInitializer
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
    public function initializeContext(Context $context)
    {
        if (!$context instanceof LoggerAwareInterface) {
            return;
        }

        $context->setBehatLogger($this->logger);
    }
}
