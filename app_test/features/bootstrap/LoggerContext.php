<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Monolog\Logger;
use Yoanm\Behat3SymfonyExtension\Context\LoggerAwareInterface;

class LoggerContext implements Context, LoggerAwareInterface
{
    const TEST_LOG_MESSAGE = 'LOG TEST : i can log something';

    /** @var Logger */
    private $logger;
    /** @var array */
    private $loggerConfig;


    /**
     * @param string $pathBase
     * @param array $loggerConfig
     */
    public function __construct($pathBase, array $loggerConfig)
    {
        $this->loggerConfig = $loggerConfig;
        $this->logFile = $pathBase.'/'.$loggerConfig['path'];
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
            '/^.*behat3Symfony\.INFO: %s \[\] \[\]$/m',
            preg_quote(self::TEST_LOG_MESSAGE, '/')
        ));
    }

    /**
     * @Given I truncate log file
     */
    public function iTruncateLogFile()
    {
        file_put_contents($this->logFile, '');
    }

    /**
     * @Then /^A log entry for request event to (?P<type>valid|exception) route must exists$/
     */
    public function aLogEntryForRequestEventToRouteTypeMustExists($type)
    {
        \PHPUnit_Framework_Assert::assertRegExp(
            sprintf(
                '/^.*behat3Symfony\.DEBUG: \[SfKernelEventLogger\] \- \[REQUEST\].*%s.*$/m',
                preg_quote(
                    'exception' === $type
                        ? MinkContext::EXCEPTION_TEST_ROUTE
                        : MinkContext::VALID_TEST_ROUTE
                    ,
                    '/'
                )
            ),
            file_get_contents($this->logFile)
        );
    }

    /**
     * @Then A log entry for kernel terminate event to valid route must exists
     */
    public function aLogEntryForKernelTerminateEventToInvalidRouteMustExists()
    {
        $this->assertLogFileMatch(
            sprintf(
                '/^.*behat3Symfony\.DEBUG: \[SfKernelEventLogger\] \- \[TERMINATE\].*%s.*$/m',
                preg_quote(MinkContext::VALID_TEST_ROUTE, '/')
            )
        );
    }

    /**
     * @Then A log entry for exception event must exists
     */
    public function aLogEntryForExceptionEventMustExists()
    {
        $this->assertLogFileMatch(
            '/^.*behat3Symfony\.ERROR: \[SfKernelEventLogger\] \- \[EXCEPTION_THROWN\].*my_exception.*$/m'
        );
    }

    /**
     * @Given logger param :property is :value
     */
    public function loggerIs($property, $value)
    {
        \PHPUnit_Framework_Assert::assertSame($value, (string)$this->loggerConfig[$property]);
    }

    /**
     * @inheritDoc
     */
    public function setBehatLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $regexp
     */
    protected function assertLogFileMatch($regexp)
    {
        \PHPUnit_Framework_Assert::assertRegExp(
            $regexp,
            file_get_contents($this->logFile)
        );
    }
}
