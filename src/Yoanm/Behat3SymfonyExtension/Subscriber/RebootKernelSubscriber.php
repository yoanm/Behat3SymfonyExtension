<?php
namespace Yoanm\Behat3SymfonyExtension\Subscriber;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;

/**
 * Class RebootKernelSubscriber
 */
class RebootKernelSubscriber implements EventSubscriberInterface
{
    /** @var KernelHandler */
    private $kernelHandler;

    /**
     * @param KernelHandler $kernelHandler
     */
    public function __construct(KernelHandler $kernelHandler)
    {
        $this->kernelHandler = $kernelHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScenarioTested::AFTER => 'rebootKernel',
            ExampleTested::AFTER => 'rebootKernel',
        ];
    }

    public function rebootKernel()
    {
        $this->kernelHandler->rebootSfKernel();
    }
}
