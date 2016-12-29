<?php
namespace FunctionalTest\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\FeatureScope;

class LoggerContext implements Context
{
    const TRUNCATE_LOGGER_FEATURE_TAG = 'truncate-log-file';

    /** @var string */
    private $logFile;

    /**
     * @param string $logFile
     */
    public function __construct($logFile)
    {
        $this->logFile = $logFile;
    }

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
            file_put_contents(__DIR__.'/../../behat.log', '');

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
                '/^.*behatUtils\.INFO: \[SfKernelEventLogger\] \[REQUEST\].*%s.*$/m',
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
            '/^.*behatUtils\.ERROR: \[SfKernelEventLogger\] \[EXCEPTION_THROWN\].*my_exception.*$/m',
            'Symfony app exception log entry not found !'
        );
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
