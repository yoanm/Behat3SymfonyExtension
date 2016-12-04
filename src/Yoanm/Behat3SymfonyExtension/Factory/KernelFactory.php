<?php
namespace Yoanm\Behat3SymfonyExtension\Factory;

use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Dispatcher\BehatKernelEventDispatcher;

class KernelFactory
{
    const KERNEL_BRIDGE_CLASS_NAME = 'YoanmBehat3SymfonyKernelBridge';
    const KERNEL_BRIDGE_TEMPLATE_COMMENT = <<<COMMENT

/******** WARNING : THIS FILE IS JUST A TEMPLATE, IT IS NOT LOADABLE AS IS ********/
COMMENT;

    /** @var BehatKernelEventDispatcher */
    private $behatKernelEventDispatcher;
    /** @var string */
    private $originalKernelPath;
    /** @var string */
    private $originalKernelClassName;

    /** @var string  */
    private $kernelEnvironment;
    /** @var bool */
    private $kernelDebug;


    /**
     * @param BehatKernelEventDispatcher $behatKernelEventDispatcher
     * @param string                     $originalKernelPath
     * @param string                     $originalKernelClassName
     * @param string                     $kernelEnvironment
     * @param bool                       $kernelDebug
     */
    public function __construct(
        BehatKernelEventDispatcher $behatKernelEventDispatcher,
        $originalKernelPath,
        $originalKernelClassName,
        $kernelEnvironment,
        $kernelDebug
    ) {
        $this->originalKernelPath = $originalKernelPath;
        $this->originalKernelClassName = $originalKernelClassName;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->kernelDebug = $kernelDebug;
        $this->behatKernelEventDispatcher = $behatKernelEventDispatcher;
    }

    /**
     * @return KernelInterface
     *
     * @throws \Exception
     */
    public function load()
    {
        // Write the custom kernel file at same level than original one for autoloading purpose
        $originAppKernelDir = dirname($this->originalKernelPath);
        $bridgeId = uniqid();
        $kernelBridgeClassName = self::KERNEL_BRIDGE_CLASS_NAME.$bridgeId;
        $customAppKernelPath = sprintf('%s/%s.php', $originAppKernelDir, $kernelBridgeClassName);
        try {
            /* /!\ YoanmBehat3SymfonyKernelBridge.php is just template file /!\ */
            $template = file_get_contents(__DIR__.'/../Bridge/YoanmBehat3SymfonyKernelBridge.php');
            file_put_contents(
                $customAppKernelPath,
                $template = str_replace(
                    ['__BridgeId__', '__OriginalKernelClassNameToReplace__', self::KERNEL_BRIDGE_TEMPLATE_COMMENT],
                    [$bridgeId, $this->originalKernelClassName, ''],
                    $template
                )
            );

            require($customAppKernelPath);
            unlink($customAppKernelPath);

            $kernelBridge = new $kernelBridgeClassName($this->kernelEnvironment, $this->kernelDebug);
            $kernelBridge->setBehatKernelEventDispatcher($this->behatKernelEventDispatcher);

            return $kernelBridge;
        } catch (\Exception $e) {
            if (file_exists($customAppKernelPath)) {
                unlink($customAppKernelPath);
            }

            throw new \Exception('An exception occured during Kernel decoration : '.$e->getMessage(), 0, $e);
        }
    }
}
