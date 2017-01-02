<?php
namespace Yoanm\Behat3SymfonyExtension\Client;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Client as BaseClient;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Event\AfterRequestEvent;
use Yoanm\Behat3SymfonyExtension\Event\BeforeRequestEvent;
use Yoanm\Behat3SymfonyExtension\Event\Events;

class Client extends BaseClient
{
    /** @var bool */
    private $requestPerformed = false;
    /** @var LoggerInterface */
    private $logger;
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        array $server,
        History $history,
        CookieJar $cookieJar
    ) {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        parent::__construct($kernel, $server, $history, $cookieJar);
        $this->disableReboot();
    }

    /**
     * @return boolean
     */
    public function resetClient()
    {
        $this->logger->debug('Resetting client');
        $this->requestPerformed = false;
        $this->rebootKernel();
    }

    /**
     * {@inheritdoc}
     */
    protected function doRequest($request)
    {
        if (true === $this->requestPerformed) {
            $this->logger->debug('A request has already been performed => reboot kernel');
            $this->rebootKernel();
        } else {
            $this->requestPerformed = true;
        }

        $this->dispatcher->dispatch(
            Events::BEFORE_REQUEST,
            new BeforeRequestEvent($request)
        );
        $response = parent::doRequest($request);
        $this->dispatcher->dispatch(
            Events::AFTER_REQUEST,
            new AfterRequestEvent($response)
        );

        return $response;
    }

    protected function rebootKernel()
    {
        // Reboot sfKernel to avoid parent::doRequest to shutdown it and from Kernel::handle to boot it
        // This behavior will allow mocking symfony app container service for instance
        $this->getKernel()->shutdown();
        $this->getKernel()->boot();
    }
}
