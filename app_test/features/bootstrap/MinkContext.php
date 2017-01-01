<?php
namespace Functional\Yoanm\Behat3SymfonyExtension\BehatContext;

use Behat\Behat\Context\Context;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Mink;
use Behat\MinkExtension\Context\MinkAwareContext;
use Yoanm\Behat3SymfonyExtension\Client\Client;
use Yoanm\Behat3SymfonyExtension\Driver\KernelDriver;

class MinkContext implements Context, MinkAwareContext
{
    const VALID_TEST_ROUTE = '/test?value=my-route-test-param';
    const EXCEPTION_TEST_ROUTE = '/exception';

    /** @var Mink */
    private $mink;

    /**
     * @Given I have mink extension
     */
    public function iHaveMinkExtension()
    {
        \PHPUnit_Framework_Assert::assertInstanceOf(
            Mink::class,
            $this->mink
        );
    }

    /**
     * @Then mink driver client must be a Client instance
     */
    public function minkDriverClientMustBeAClientInstance()
    {
        \PHPUnit_Framework_Assert::assertInstanceOf(
            BrowserKitDriver::class,
            $this->getDriver()
        );
        \PHPUnit_Framework_Assert::assertInstanceOf(
            Client::class,
            $this->getDriver()->getClient()
        );
    }

    /**
     * @Then I call my symfony app with a valid route
     */
    public function iCallMySymfonyAppWithAValidRoute()
    {
        $this->getDriver()->visit(self::VALID_TEST_ROUTE);
    }

    /**
     * @Then I call my symfony app with an exception route
     */
    public function iCallMySymfonyAppWithAnExceptionRoute()
    {
        try {
            $this->getDriver()->visit(self::EXCEPTION_TEST_ROUTE);
        } catch (\Exception $e) {
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMink(Mink $mink)
    {
        $this->mink = $mink;
    }

    /**
     * {@inheritdoc}
     */
    public function setMinkParameters(array $parameters)
    {
    }

    /**
     * @return DriverInterface|BrowserKitDriver
     */
    protected function getDriver()
    {
        return $this->mink->getSession()->getDriver();
    }
}
