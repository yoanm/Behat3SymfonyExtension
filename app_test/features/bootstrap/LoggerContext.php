<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\FeatureScope;
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

    /**
     * @param string $logFile
     */
    public function __construct($logFile)
    {
        $this->logFile = $logFile;
    }

    /** LOGGER AWARE STEPS */
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
    /** END - LOGGER AWARE STEPS */

    /**
     * @Given I truncate log file
     */
    public function iTruncateLogFile()
    {
        self::truncateLogFile($this->logFile);
    }

    /**
     * @param string|null $file
     */
    public static function truncateLogFile($file = null)
    {
        if (null === $file) {
            // Tricks => clean log file before all features
            file_put_contents(__DIR__.'/../../var/log/behat.log', '');
            file_put_contents(__DIR__.'/../../var/log/behat2.log', '');

            return;
        }
        file_put_contents($file, '');
    }

    /**
     * @BeforeFeature
     */
    public static function cleanBeforeFeature(FeatureScope $scope)
    {
        if (in_array(
            self::TRUNCATE_LOGGER_FEATURE_TAG,
            $scope->getFeature()->getTags()
        )) {
            self::truncateLogFile();
        }
    }

    /**
     * @Then /^A log entry must exist for symfony app request to (?P<type>valid|exception) route$/
     */
    public function aLogEntryForRequestToRouteTypeMustExists($type)
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
            'Symfony app request log entry not found !'
        );
    }

    /**
     * @Then A log entry must exist for symfony app exception
     */
    public function aLogEntryForExceptionEventMustExists()
    {
        $this->assertLogFileMatch(
            '/^.*behat3Symfony\.ERROR: \[SfKernelEventLogger\] \[EXCEPTION_THROWN\].*my_exception.*$/m',
            'Symfony app exception log entry not found !'
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
}
