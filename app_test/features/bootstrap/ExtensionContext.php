<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Yoanm\Behat3SymfonyExtension\Driver\KernelDriver;

class ExtensionContext implements Context
{
    /** @var array */
    private $extensionConfig;

    /**
     * @param array $extensionConfig
     */
    public function __construct(array $extensionConfig)
    {
        $this->extensionConfig = $extensionConfig;
    }

    /**
     * @Given /^extension param "(?P<property>[^"]+)" is (?P<value>true|false+)$/
     */
    public function extensionPropertyIsBool($property, $value)
    {
        \PHPUnit_Framework_Assert::assertSame('true' === $value, $this->extensionConfig[$property]);
    }
}
