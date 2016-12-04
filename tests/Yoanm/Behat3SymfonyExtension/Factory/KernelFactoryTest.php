<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Factory;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Config\Definition\Exception\Exception;
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
    /** @var KernelFactory */
    private $factory;


    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        MockYoanmBehat3SymfonyKernelBridge::$throwExceptionOnStartup = false;
        $this->behatKernelEventDispatcher = $this->prophesize(BehatKernelEventDispatcher::class);
        $this->originalKernelPath = __DIR__.'/../Bridge/MockYoanmBehat3SymfonyKernelBridge.php';
        $this->originalKernelClassName = MockYoanmBehat3SymfonyKernelBridge::class;
        $this->kernelEnvironment = 'custom_test';
        $this->kernelDebug = true;

        $this->factory = new KernelFactory(
            $this->behatKernelEventDispatcher->reveal(),
            $this->originalKernelPath,
            $this->originalKernelClassName,
            $this->kernelEnvironment,
            $this->kernelDebug
        );
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
        $this->factory = new KernelFactory(
            $this->behatKernelEventDispatcher->reveal(),
            $this->originalKernelPath,
            $this->originalKernelClassName,
            $this->kernelEnvironment,
            $this->kernelDebug
        );

        MockYoanmBehat3SymfonyKernelBridge::$throwExceptionOnStartup = true;
        try {
            $this->setExpectedException(\Exception::class, 'my-custom-message');
            $this->factory->load();
        } catch (\Exception $e) {
            $this->assertKernelBridgeFileHasBeenDeleted();

            throw $e;
        }
    }

    protected function assertKernelBridgeFileHasBeenDeleted($kernel = null)
    {
        $originAppKernelDir = dirname($this->originalKernelPath);
        if (null === $kernel) {
            $fileList = glob(sprintf('%s/%s*.php', $originAppKernelDir, KernelFactory::KERNEL_BRIDGE_CLASS_NAME));
            $this->assertEmpty($fileList, 'Failed asserting that bridge file is removed !');
        } else {
            $kernelBridgeClassName = get_class($kernel);
            $this->assertFileNotExists(
                sprintf('%s/%s.php', $originAppKernelDir, $kernelBridgeClassName),
                'Failed asserting that bridge file is removed !'
            );
        }
    }
}
