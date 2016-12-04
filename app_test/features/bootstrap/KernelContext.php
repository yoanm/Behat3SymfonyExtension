<?php
namespace FunctionalTest;

use Behat\Behat\Context\Context;
use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Context\KernelAwareInterface;

class KernelContext implements Context, KernelAwareInterface
{
    /** @var KernelInterface */
    private $kernel;
    /** @var array */
    private $kernelConfig;
    /** @var string|null */
    private static $lastContainerInstance = null;

    public function __construct(array $kernelConfig)
    {
        $this->kernelConfig = $kernelConfig;
    }

    /**
     * @Given I have access to symfony app container
     */
    public function iHaveAccessToSymfonyAppKernel()
    {
        \PHPUnit_Framework_Assert::assertInstanceOf(
            KernelInterface::class,
            $this->kernel
        );
    }
    /**
     * @Then The container test parameter is set
     */
    public function containerTestParameterShouldBeDefined()
    {
        \PHPUnit_Framework_Assert::assertTrue($this->kernel->getContainer()->hasParameter('container_test_parameter'));
        \PHPUnit_Framework_Assert::assertSame(
            'my-container-test-parameter',
            $this->kernel->getContainer()->getParameter('container_test_parameter')
        );
    }


    /**
     * @Given /^kernel param "(?P<property>[^"]+)" is "(?P<value>[^"]+)"$/
     */
    public function kernelPropertyIs($property, $value)
    {
        \PHPUnit_Framework_Assert::assertSame($value, (string)$this->kernelConfig[$property]);
    }

    /**
     * @Given /^kernel param "(?P<property>[^"]+)" is (?P<value>true|false+)$/
     */
    public function kernelPropertyIsBoold($property, $value)
    {
        \PHPUnit_Framework_Assert::assertSame('true' === $value, $this->kernelConfig[$property]);
    }

    /**
     * @Given I backup container instance
     */
    public function iBackupContainerInstance()
    {
        self::$lastContainerInstance = spl_object_hash($this->kernel->getContainer());
    }

    /**
     * @Given Current container instance has changed
     */
    public function currentContainerInstanceHasChanged()
    {
        \PHPUnit_Framework_Assert::assertNotNull(self::$lastContainerInstance);
        \PHPUnit_Framework_Assert::assertNotEquals(
            self::$lastContainerInstance,
            spl_object_hash($this->kernel->getContainer())
        );
        self::$lastContainerInstance = null;
    }

    /**
     * @When I shutdown symfony kernel
     */
    public function iCanShutdownSymfonyKernel()
    {
        $this->kernel->shutdown();
    }

    /**
     * @When I boot symfony kernel
     */
    public function iCanBootSymfonyKernel()
    {
        $this->kernel->boot();
    }


    /**
     * @inheritDoc
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }
}
