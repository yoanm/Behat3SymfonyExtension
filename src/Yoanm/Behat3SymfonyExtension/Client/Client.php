<?php
namespace Yoanm\Behat3SymfonyExtension\Client;

use Symfony\Bundle\FrameworkBundle\Client as BaseClient;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Handler\KernelHandler;

class Client extends BaseClient
{
    /** @var KernelHandler */
    private $kernelHandler;
    /** @var bool */
    private $requestPerformed = false;

    /**
     * @inheritDoc
     */
    public function __construct(
        KernelHandler $kernelHandler,
        KernelInterface $kernel,
        array $server,
        History $history,
        CookieJar $cookieJar
    ) {
        parent::__construct($kernel, $server, $history, $cookieJar);
        $this->kernelHandler = $kernelHandler;
        $this->disableReboot();
    }

    /**
     * @return boolean
     */
    public function hasRequestPerformed()
    {
        return $this->requestPerformed;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRequest($request)
    {
        if ($this->requestPerformed) {
            // Reboot sfKernel to avoid parent::doRequest to shutdown it and from Kernel::handle to boot it
            $this->kernelHandler->rebootSfKernel();
        } else {
            $this->requestPerformed = true;
        }

        return parent::doRequest($request);
    }
}
