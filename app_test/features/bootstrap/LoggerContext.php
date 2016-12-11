<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Behat\Behat\EventDispatcher\Event\GherkinNodeTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Testwork\EventDispatcher\Event\AfterTested;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Yoanm\Behat3SymfonyExtension\Context\LoggerAwareInterface;

class LoggerContext implements Context, LoggerAwareInterface
{
    const TRUNCATE_LOGGER_FEATURE_TAG = 'truncate-log-file';
    const TEST_LOG_MESSAGE = 'LOG TEST : i can log something';

    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $logFile;
    /** @var string */
    private static $lastLogFile;

    /**
     * @param string $logFile
     */
    public function __construct($logFile)
    {
        self::$lastLogFile = $this->logFile = $logFile;
    }

    /**
     * @Given I have access to a logger
     */
    public function iHaveAccessToALogger()
    {
        \PHPUnit_Framework_Assert::assertInstanceOf(
            Logger::class,
            $this->logger
        );
    }

    /**
     * @When I log a test message
     */
    public function iLogATestMessage()
    {
        $this->logger->info(self::TEST_LOG_MESSAGE);
    }

    /**
     * @Then Test message is in log file
     */
    public function iLogSomething()
    {
        $this->assertLogFileMatch(sprintf(
            '/^.*behat3Symfony\.INFO: \[LoggerContext\] %s \[\] \[\]$/m',
            preg_quote(self::TEST_LOG_MESSAGE, '/'),
            'Test log sentence not found !'
        ));
    }

    /**
     * @Given I truncate log file
     */
    public function iTruncateLogFile()
    {
        self::truncateLogFile($this->logFile);
    }

    /**
     * @AfterFeature
     * @param string|null $file
     */
    public static function truncateLogFile($file = null)
    {
        if (null !== $file) {
            if (is_file(self::$lastLogFile)) {
                file_put_contents(self::$lastLogFile, '');
            }
        } else {
            file_put_contents($file, '');
        }
    }

    /**
     * @Then /^A log entry must exist for symfony app request event to (?P<type>valid|exception) route$/
     */
    public function aLogEntryForRequestEventToRouteTypeMustExists($type)
    {
        $this->assertLogFileMatch(
            sprintf(
                '/^.*behat3Symfony\.INFO: \[SfKernelEventLogger\] \[REQUEST\].*%s.*$/m',
                preg_quote(
                    'exception' === $type
                        ? MinkContext::EXCEPTION_TEST_ROUTE
                        : MinkContext::VALID_TEST_ROUTE
                    ,
                    '/'
                )
            ),
            'Request event log entry not found !'
        );
    }

    /**
     * @Then A log entry must exist for symfony app exception event
     */
    public function aLogEntryForExceptionEventMustExists()
    {
        $this->assertLogFileMatch(
            '/^.*behat3Symfony\.ERROR: \[SfKernelEventLogger\] \[EXCEPTION_THROWN\].*my_exception.*$/m',
            'Exception event log entry not found !'
        );
    }

    /**
     * @Given A log entry must exist for current step start event and I will have the one regarding end event
     */
    public function aLogEntryMustExistForCurrentStepNodeStartEndEvent()
    {
        $this->assertLogFileMatch(
            '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[STEP\]\[IN\].*$/m',
            'Start step event log entry not found !'
        );
        $this->expectStepEndEventEntry = true;
    }

    /**
     * @Given A log entry must exist for current example start event using var :arg1
     */
    public function aLogEntryMustExistForCurrentExampleStartEvent($arg1)
    {
        $this->assertLogFileMatch(
            sprintf(
                '/^.*behat3Symfony\.DEBUG: \[BehatStepLoggerSubscriber\] \[SCENARIO EXAMPLE\]\[IN\] \{"tokens":%s.*$/m',
                preg_quote(json_encode(['var' => $arg1]), '/')
            ),
            'Start example event log entry not found !'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setBehatLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $regexp
     */
    protected function assertLogFileMatch($regexp, $message = '')
    {
        \PHPUnit_Framework_Assert::assertRegExp(
            $regexp,
            file_get_contents($this->logFile),
            $message
        );
    }


    /**
     * @param GherkinNodeTested $event
     */
    private function checkStepEventAssertionIfNeeded(GherkinNodeTested $event)
    {
        if ($event instanceof StepTested) {
            if ($event instanceof AfterTested) { // AFTER
                $this->expectStepEndEvent = false;
            } else { // BEFORE
                // Check if previous step had an expectation on step end event
                $expectStepEndEventEntryBackup = $this->expectStepEndEventEntry;
                $this->expectStepEndEventEntry = false;
                \PHPUnit_Framework_Assert::assertFalse(
                    $expectStepEndEventEntryBackup,
                    'Step end event expected but not catched !'
                );
            }
        }
    }
}
