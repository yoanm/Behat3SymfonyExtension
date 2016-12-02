<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\AbstractExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Driver\Behat3SymfonyDriverFactory;

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
    /**
     * @inheritDoc
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        $minExtension = $extensionManager->getExtension('mink');

        if ($minExtension) {
            $minExtension->registerDriverFactory(new Behat3SymfonyDriverFactory());
        }
    }
    // @codeCoverageIgnoreEnd

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

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $kernelConfig = $config[$this->getConfigKey()];
        $container->setParameter(
            $this->buildContainerId('kernel.reboot'),
            $kernelConfig['reboot']
        );
        $container->setParameter(
            $this->buildContainerId('kernel.bootstrap'),
            $kernelConfig['bootstrap']
        );
        $this->createService(
            $container,
            'kernel',
            $kernelConfig['class'],
            [
                $kernelConfig['env'],
                $kernelConfig['debug'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $bootstrapPath = $container->getParameter($this->buildContainerId('kernel.bootstrap'));
        if ($bootstrapPath) {
            $bootstrap = sprintf(
                '%s/%s',
                $container->getParameter('paths.base'),
                $bootstrapPath
            );
            if (file_exists($bootstrap)) {
                require_once($bootstrap);
            } else {
                throw new ProcessingException('Could not find bootstrap file !');
            }
        }
    }
}
