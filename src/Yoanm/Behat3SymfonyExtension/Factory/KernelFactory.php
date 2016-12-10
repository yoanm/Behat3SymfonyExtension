<?php
namespace Yoanm\Behat3SymfonyExtension\Factory;

use Psr\Log\LoggerInterface;
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
    /** @var LoggerInterface */
    private $logger;
    /** @var bool */
    private $debugMode;


    /**
     * KernelFactory constructor.
     * @param BehatKernelEventDispatcher $behatKernelEventDispatcher
     * @param array                      $kernelConfig
     * @param boolean                    $debugMode
     */
    public function __construct(
        BehatKernelEventDispatcher $behatKernelEventDispatcher,
        LoggerInterface $logger,
        array $kernelConfig,
        $debugMode = false
    ) {
        $this->behatKernelEventDispatcher = $behatKernelEventDispatcher;
        $this->logger = $logger;
        $this->debugMode = $debugMode;
        $this->kernelConfig = $kernelConfig;
    }

    /**
     * @return KernelInterface
     *
     * @throws \Exception
     */
    public function load()
    {
        // Write the custom kernel file at same level than original one for autoloading purpose
        $originAppKernelDir = dirname($this->kernelConfig['path']);
        $bridgeId = uniqid();
        $kernelBridgeClassName = self::KERNEL_BRIDGE_CLASS_NAME.$bridgeId;
        $customAppKernelPath = sprintf('%s/%s.php', $originAppKernelDir, $kernelBridgeClassName);

        $this->logger->debug('Kernel bridge file path : "{file_path}"', ['file_path' => $customAppKernelPath]);
        try {
            /* /!\ YoanmBehat3SymfonyKernelBridge.php is just template file /!\ */
            $kernelBridgeTemplateFile = __DIR__.'/../Bridge/YoanmBehat3SymfonyKernelBridge.php';
            $this->logger->debug(
                'Loading kernel bridge template: "{file_path}"',
                ['file_path' => $kernelBridgeTemplateFile]
            );
            $template = file_get_contents($kernelBridgeTemplateFile);

            $this->logger->debug('Writing kernel bridge class');
            file_put_contents(
                $customAppKernelPath,
                $template = str_replace(
                    ['__BridgeId__', '__OriginalKernelClassNameToReplace__', self::KERNEL_BRIDGE_TEMPLATE_COMMENT],
                    [$bridgeId, $this->kernelConfig['class'], ''],
                    $template
                )
            );

            $this->logger->debug('Loading kernel bridge ...');
            require($customAppKernelPath);
            $this->logger->debug('Loading kernel bridge : DONE');

            $this->cleanKernelBridgeFile($customAppKernelPath);

            $this->logger->debug(
                'Instanciate kernel bridge with [env: {env}, debug: {debug}]',
                [
                    'env' => $this->kernelConfig['env'],
                    'debug' => $this->kernelConfig['debug'],
                ]
            );

            $kernelBridge = new $kernelBridgeClassName($this->kernelConfig['env'], $this->kernelConfig['debug']);
            $kernelBridge->setBehatKernelEventDispatcher($this->behatKernelEventDispatcher);

            $this->logger->debug('Kernel bridge init DONE');

            return $kernelBridge;
        } catch (\Exception $e) {
            $this->logger->error('Exception during kernel bridge init : "{message}"', ['message' => $e->getMessage()]);
            $this->cleanKernelBridgeFile($customAppKernelPath);
            throw new \Exception('An exception occured during Kernel decoration : '.$e->getMessage(), 0, $e);
        }
    }

    protected function cleanKernelBridgeFile($file)
    {
        if (false === $this->debugMode) {
            if (file_exists($file)) {
                $this->logger->debug('Removing kernel bridge file: "{file_path}"', ['file_path' => $file]);
                unlink($file);
            }
            //clean old bridge files too
            array_map(
                'unlink',
                glob(sprintf(
                    '%s/%s*.php',
                    dirname($this->kernelConfig['path']),
                    KernelFactory::KERNEL_BRIDGE_CLASS_NAME
                ))
            );
        }
    }
}
