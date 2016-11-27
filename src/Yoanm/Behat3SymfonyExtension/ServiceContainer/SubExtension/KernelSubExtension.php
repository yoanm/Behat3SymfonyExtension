<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KernelSubExtension extends AbstractSubExtension
{
    /**
     * @inheritDoc
     */
    public function getConfigKey()
    {
        return 'kernel';
    }

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
            $this->getContainerParamOrServiceId('kernel.reboot'),
            $kernelConfig['reboot']
        );
        $this->createService(
            $container,
            'kernel',
            $kernelConfig['class'],
            array(
                $kernelConfig['env'],
                $kernelConfig['debug'],
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $bootstrapPath = $container->getParameter($this->getContainerParamOrServiceId('kernel.bootstrap'));
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
