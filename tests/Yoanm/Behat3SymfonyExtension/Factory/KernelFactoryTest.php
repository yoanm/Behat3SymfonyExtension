<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Factory;

use Monolog\Logger;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\Yoanm\Behat3SymfonyExtension\Bridge\MockYoanmBehat3SymfonyKernelBridge;
use Yoanm\Behat3SymfonyExtension\Dispatcher\BehatKernelEventDispatcher;
use Yoanm\Behat3SymfonyExtension\Factory\KernelFactory;

class KernelFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var BehatKernelEventDispatcher|ObjectProphecy */
    private $behatKernelEventDispatcher;
    /** @var string */
    private $originalKernelPath;
    /** @var string */
    private $originalKernelClassName;
    /** @var string  */
    private $kernelEnvironment;
    /** @var bool */
    private $kernelDebug;
    /** @var Logger|ObjectProphecy */
    private $logger;
    /** @var KernelFactory */
    private $factory;
    /** @var bool */
    private $debugMode;


    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        MockYoanmBehat3SymfonyKernelBridge::$throwExceptionOnStartup = false;
        $this->behatKernelEventDispatcher = $this->prophesize(BehatKernelEventDispatcher::class);
        $this->logger = $this->prophesize(Logger::class);
        $this->originalKernelPath = __DIR__.'/../Bridge/MockYoanmBehat3SymfonyKernelBridge.php';
        $this->originalKernelClassName = MockYoanmBehat3SymfonyKernelBridge::class;
        $this->kernelEnvironment = 'custom_test';
        $this->kernelDebug = true;
        $this->debugMode = false;

        $this->factory = new KernelFactory(
            $this->behatKernelEventDispatcher->reveal(),
            $this->logger->reveal(),
            [
                'path' => $this->originalKernelPath,
                'class' => $this->originalKernelClassName,
                'env' => $this->kernelEnvironment,
                'debug' => $this->kernelDebug,
            ],
            $this->debugMode
        );
    }

    public function tearDown()
    {
        $this->cleanKernelBridgeFile();
        parent::tearDown();
    }

    /**
     * @return KernelInterface
     *
     * @throws \Exception
     */
    public function testLoad()
    {
        $kernel = $this->factory->load();

        $this->assertInstanceOf(MockYoanmBehat3SymfonyKernelBridge::class, $kernel);

        $this->assertAttributeSame(
            $this->behatKernelEventDispatcher->reveal(),
            'behatKernelEventDispatcher',
            $kernel
        );
        $this->assertKernelBridgeFileHasBeenDeleted($kernel);
    }

    /**
     * @return KernelInterface
     *
     * @throws \Exception
     */
    public function testLoadWithException()
    {
        MockYoanmBehat3SymfonyKernelBridge::$throwExceptionOnStartup = true;
        try {
            $this->setExpectedException(
                \Exception::class,
                'An exception occured during Kernel decoration : my-custom-message'
            );
            $this->factory->load();
        } catch (\Exception $e) {
            $this->assertKernelBridgeFileHasBeenDeleted();
            throw $e;
        }
    }

    public function testKernelBridgeFileNotDeletedInDebugMode()
    {
        $this->debugMode = true;
        $this->factory = new KernelFactory(
            $this->behatKernelEventDispatcher->reveal(),
            $this->logger->reveal(),
            [
                'path' => $this->originalKernelPath,
                'class' => $this->originalKernelClassName,
                'env' => $this->kernelEnvironment,
                'debug' => $this->kernelDebug,
            ],
            $this->debugMode
        );
        $kernel = $this->factory->load();

        $this->assertInstanceOf(MockYoanmBehat3SymfonyKernelBridge::class, $kernel);

        $this->assertAttributeSame(
            $this->behatKernelEventDispatcher->reveal(),
            'behatKernelEventDispatcher',
            $kernel
        );
        $this->assertKernelBridgeFileHasBeenDeleted($kernel, true);
    }

    protected function assertKernelBridgeFileHasBeenDeleted($kernel = null, $notDeleted = false)
    {
        $originAppKernelDir = dirname($this->originalKernelPath);
        $message = 'Failed asserting that bridge file is removed !';
        $constraint = new \PHPUnit_Framework_Constraint_FileExists();
        if (null === $kernel) {
            $constraint = self::isEmpty();
            $fileNamePattern = sprintf('%s/%s*.php', $originAppKernelDir, KernelFactory::KERNEL_BRIDGE_CLASS_NAME);
            $actual = glob($fileNamePattern);
            if (true === $notDeleted) {
                $constraint = self::logicalNot($constraint);
                $message = 'Failed asserting that a bridge file is still there !';
            } else {
                $message = 'Failed asserting that all bridge files have been deleted !';
            }
            $message .= ' ('.$fileNamePattern.')';
        } else {
            $actual = sprintf('%s/%s.php', $originAppKernelDir, get_class($kernel));
            if (true === $notDeleted) {
                $message = 'Failed asserting that bridge file is still there!';
            } else {
                $constraint = self::logicalNot($constraint);
            }
            $message .= ' ('.$actual.')';
        }
        self::assertThat($actual, $constraint, $message);
    }

    protected function cleanKernelBridgeFile()
    {
        array_map(
            'unlink',
            glob(sprintf('%s/%s*.php', dirname($this->originalKernelPath), KernelFactory::KERNEL_BRIDGE_CLASS_NAME))
        );
    }
}
