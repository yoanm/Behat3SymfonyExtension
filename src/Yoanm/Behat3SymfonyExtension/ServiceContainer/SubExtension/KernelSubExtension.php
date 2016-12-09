<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\AbstractExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\DriverFactory\Behat3SymfonyFactory;

class KernelSubExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getConfigKey()
    {
        return 'kernel';
    }

    // @codeCoverageIgnoreStart
    // Not possible to cover this because ExtensionManager is a final class
    // Will be covered by FT
    /**
     * @inheritDoc
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        $minExtension = $extensionManager->getExtension('mink');
        if ($minExtension instanceof MinkExtension) {
            $minExtension->registerDriverFactory(new Behat3SymfonyFactory());
        }
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    // Will be covered by FT
    /**
     * @inheritDoc
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $castToBool = function ($value) {
            $filtered = filter_var(
                $value,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            return (null === $filtered) ? (bool) $value : $filtered;
        };
        $builder
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('bootstrap')
                    ->defaultValue('app/autoload.php')
                ->end()
                ->scalarNode('path')
                    ->defaultValue('app/AppKernel.php')
                ->end()
                ->scalarNode('class')
                    ->defaultValue('AppKernel')
                ->end()
                ->scalarNode('env')
                    ->defaultValue('test')
                ->end()
                ->booleanNode('debug')
                    ->beforeNormalization()
                    ->always()
                        ->then($castToBool)
                    ->end()
                    ->defaultTrue()
                ->end()
                ->booleanNode('reboot')
                    ->info('If true symfony kernel will be rebooted after each scenario/example')
                    ->beforeNormalization()
                        ->always()
                        ->then($castToBool)
                    ->end()
                    ->defaultTrue()
                ->end()
            ->end();
    }
    // @codeCoverageIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $basePath = $container->getParameter('paths.base');
        $bootstrapPath = $container->getParameter($this->buildContainerId('kernel.bootstrap'));
        if ($bootstrapPath) {
            $bootstrapPathUnderBasePath = sprintf('%s/%s', $basePath, $bootstrapPath);
            if (file_exists($bootstrapPathUnderBasePath)) {
                $bootstrapPath = $bootstrapPathUnderBasePath;
            }
            if (file_exists($bootstrapPath)) {
                require_once($bootstrapPath);
            } else {
                throw new ProcessingException('Could not find bootstrap file !');
            }
        }

        // load kernel
        $kernelPath = $container->getParameter($this->buildContainerId('kernel.path'));
        $kernelPathUnderBasePath = sprintf('%s/%s', $basePath, $kernelPath);
        if (file_exists($kernelPathUnderBasePath)) {
            $kernelPath = $kernelPathUnderBasePath;
        }

        $container->getDefinition(self::KERNEL_SERVICE_ID)
            ->setFile($kernelPath);
    }
}
