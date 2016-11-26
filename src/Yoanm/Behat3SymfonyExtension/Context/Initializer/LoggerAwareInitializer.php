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
    private $behatLogger;

    /**
     * @param Logger $behatLogger
     */
    public function __construct(Logger $behatLogger)
    {
        $this->behatLogger = $behatLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        if (!$context instanceof LoggerAwareInterface) {
            return;
        }

        $context->setBehatLogger($this->behatLogger);
    }
}
