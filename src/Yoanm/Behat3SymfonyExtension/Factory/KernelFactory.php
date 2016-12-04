<?php
namespace Yoanm\Behat3SymfonyExtension\Factory;

use Symfony\Component\HttpKernel\KernelInterface;
use Yoanm\Behat3SymfonyExtension\Dispatcher\BehatKernelEventDispatcher;

class KernelFactory
{
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
        $className = 'YoanmBehat3SymfonyKernelBridge';
        $originalKernelClassName = $this->originalKernelClassName;
        $template = <<<TEMPLATE
<?php
/**
 * Autogenerated by Behat3SymfonyExtension.
 * Don't touch the content it will be erased !
 * See Yoanm\Behat3SymfonyExtension\Factory\KernelFactory::load()
 *
 * This file should be automatically deleted after kernel load. Except if kernel.kernelDebug === true
 */
use Yoanm\Behat3SymfonyExtension\Dispatcher\BehatKernelEventDispatcher;
use ${originalKernelClassName} as ${className}BaseKernel;

class $className extends ${className}BaseKernel
{
    /** @var BehatKernelEventDispatcher */
    private \$behatKernelEventDispatcher;

    /**
     * @param BehatKernelEventDispatcher \$behatKernelEventDispatcher
     */
    public function setBehatKernelEventDispatcher(BehatKernelEventDispatcher \$behatKernelEventDispatcher)
    {
        \$this->behatKernelEventDispatcher = \$behatKernelEventDispatcher;
    }

    /**
     * Will dispatch events related to kernel boot action
     * Rely on parent class method
     *
     * {@inheritdoc}
     */
    public function boot()
    {
        \$this->behatKernelEventDispatcher->beforeBoot(\$this);
        parent::boot();
        \$this->behatKernelEventDispatcher->afterBoot(\$this);
    }

    /**
     * Will dispatch events related to kernel shutdown action
     * Rely on parent class method
     *
     * {@inheritdoc}
     */
    public function shutdown()
    {
        \$this->behatKernelEventDispatcher->beforeShutdown(\$this);
        parent::shutdown();
        \$this->behatKernelEventDispatcher->afterShutdown(\$this);
    }
}

TEMPLATE;

        return $this->createAndLoadCustomAppKernel($template, $className);
    }

    /**
     * @param $template
     * @param $className
     * @return mixed
     * @throws \Exception
     */
    protected function createAndLoadCustomAppKernel($template, $className)
    {
        // Write the custom kernel file at same level than original one for autoloading purpose
        $originAppKernelDir = dirname($this->originalKernelPath);
        $customAppKernelPath = sprintf('%s/%s.php', $originAppKernelDir, $className);
        try {
            file_put_contents($customAppKernelPath, $template);

            require($customAppKernelPath);
            unlink($customAppKernelPath);

            $class = new $className($this->kernelEnvironment, $this->kernelDebug);
            $class->setBehatKernelEventDispatcher($this->behatKernelEventDispatcher);

            return $class;
        } catch (\Exception $e) {
            unlink($customAppKernelPath);
            throw new \Exception(
                'An exception occured during Kernel decoration : '.$e->getMessage(),
                0,
                $e
            );
        }
    }
}
