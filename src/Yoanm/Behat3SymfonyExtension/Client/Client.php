<?php
namespace Yoanm\Behat3SymfonyExtension\Client;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Client as BaseClient;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\HttpKernel\KernelInterface;

class Client extends BaseClient
{
    /** @var bool */
    private $requestPerformed = false;
    /** @var LoggerInterface */
    private $logger;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        array $server,
        History $history,
        CookieJar $cookieJar
    ) {
        $this->logger = $logger;
        parent::__construct($kernel, $server, $history, $cookieJar);
        $this->disableReboot();
    }

    /**
     * @return boolean
     */
    public function hasPerformedRequest()
    {
        return $this->requestPerformed;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRequest($request)
    {
        if ($this->requestPerformed) {
            $this->logger->debug('A request has already been performed => reboot kernel');
            // Reboot sfKernel to avoid parent::doRequest to shutdown it and from Kernel::handle to boot it
            // This behavior will allow mocking symfony app container service for instance
            $this->getKernel()->shutdown();
            $this->getKernel()->boot();
        } else {
            $this->requestPerformed = true;
        }

        return parent::doRequest($request);
    }
}
