<?php
namespace Technical\Unit\Yoanm\Behat3SymfonyExtension\Bridge;

use Yoanm\Behat3SymfonyExtension\Dispatcher\BehatKernelEventDispatcher;

class MockYoanmBehat3SymfonyKernelBridge
{
    /** @var bool */
    public static $throwExceptionOnStartup = false;

    public function __construct()
    {
        if (true === self::$throwExceptionOnStartup) {
            throw new \Exception('my-custom-message');
        }
    }

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
