<?php
namespace Functional\Yoanm\Behat3SymfonyExtension\Context;

use Behat\Behat\Context\Context;

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
     * @Given /^extension (?:(?P<key>kernel|logger) )?config "(?P<property>[^"]+)" is (?P<value>true|false+)$/
     */
    public function extensionConfigIsBool($key, $property, $value)
    {
        $this->extensionConfigIs($key, $property, 'true' === $value);
    }

    /**
     * @Given /^extension (?:(?P<key>kernel|logger) )?config "(?P<property>[^"]+)" is (?P<value>\d+)$/
     */
    public function extensionConfigIsInt($key, $property, $value)
    {
        $this->extensionConfigIs($key, $property, (int)$value);
    }

    /**
     * @Given /^extension (?:(?P<key>kernel|logger) )?config "(?P<property>[^"]+)" is "(?P<value>[^"]+)"$/
     */
    public function extensionConfigIs($key, $property, $value)
    {
        \PHPUnit_Framework_Assert::assertSame(
            $value,
            $this->getExtensionConfigFor($key)[$property]
        );
    }

    /**
     * @param string $key
     * @return array
     */
    protected function getExtensionConfigFor($key)
    {
        $config = $this->extensionConfig;
        if (in_array($key, ['logger', 'kernel'])) {
            $config = $this->extensionConfig[$key];
        }

        return $config;
    }
}
