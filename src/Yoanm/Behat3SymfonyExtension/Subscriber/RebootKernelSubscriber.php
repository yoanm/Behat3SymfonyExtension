<?php
namespace Yoanm\Behat3SymfonyExtension\Subscriber;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class RebootKernelSubscriber
 */
class RebootKernelSubscriber implements EventSubscriberInterface
{
    /** @var KernelInterface */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScenarioTested::BEFORE => 'rebootKernel',
            ExampleTested::BEFORE => 'rebootKernel',
        ];
    }

    public function rebootKernel()
    {
        $this->kernel->shutdown();
        $this->kernel->boot();
    }
}
