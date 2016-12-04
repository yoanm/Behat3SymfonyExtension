<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\Factory;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\Yoanm\Behat3SymfonyExtension\Bridge\YoanmBehat3SymfonyKernelBridgeMock;
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
        $this->behatKernelEventDispatcher = $this->prophesize(BehatKernelEventDispatcher::class);
        $this->originalKernelPath = __DIR__.'/../Bridge/YoanmBehat3SymfonyKernelBridgeMock.php';
        $this->originalKernelClassName = YoanmBehat3SymfonyKernelBridgeMock::class;
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

        $this->assertInstanceOf(YoanmBehat3SymfonyKernelBridgeMock::class, $kernel);

        $this->assertAttributeSame(
            $this->behatKernelEventDispatcher->reveal(),
            'behatKernelEventDispatcher',
            $kernel
        );
    }
}
