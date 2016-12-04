<?php
namespace Yoanm\Behat3SymfonyExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Monolog\Logger;
use Yoanm\Behat3SymfonyExtension\Context\LoggerAwareInterface;

/**
 * Class LoggerAwareInitializer
 */
class LoggerAwareInitializer implements ContextInitializer
{
    /** @var Logger */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
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
