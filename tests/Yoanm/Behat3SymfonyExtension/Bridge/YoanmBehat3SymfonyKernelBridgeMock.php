<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Bridge;

use Yoanm\Behat3SymfonyExtension\Dispatcher\BehatKernelEventDispatcher;

class YoanmBehat3SymfonyKernelBridgeMock
{
    /**
     * @param BehatKernelEventDispatcher $behatKernelEventDispatcher
     */
    public function setBehatKernelEventDispatcher(BehatKernelEventDispatcher $behatKernelEventDispatcher)
    {
    }

    public function boot()
    {
    }

    public function shutdown()
    {
    }
}
