<?php
namespace Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\Context\Initializer\BehatContextSubscriberInitializer;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\KernelSubExtension;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension\LoggerSubExtension;

class Behat3SymfonyExtension extends AbstractExtension
{
    /** @var Extension[] */
    private $subExtensionList = [];

    public function __construct(Extension $kernelSubExtension = null, Extension $loggerSubExtension = null)
    {
        $this->subExtensionList[] = $kernelSubExtension ?: new KernelSubExtension();
        $this->subExtensionList[] = $loggerSubExtension ?: new LoggerSubExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'behat3_symfony';
    }

    // @codeCoverageIgnoreStart
    // Not possible to cover this because ExtensionManager is a final class
    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        foreach ($this->subExtensionList as $subExtension) {
            $subExtension->initialize($extensionManager);
        }
    }
    // @codeCoverageIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->children()
            ->booleanNode('debug_mode')
                ->beforeNormalization()
                    ->always()
                    ->then(function ($value) {
                        $filtered = filter_var(
                            $value,
                            FILTER_VALIDATE_BOOLEAN,
                            FILTER_NULL_ON_FAILURE
                        );

                        return (null === $filtered) ? (bool) $value : $filtered;
                    })
                    ->end()
                ->defaultFalse()
            ->end()
            ;
        foreach ($this->subExtensionList as $subExtension) {
            $subExtension->configure(
                $builder->children()
                    ->arrayNode($subExtension->getConfigKey())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $container->setParameter(
            '%'.$this->buildContainerId('debug_mode').'%',
            $config['debug_mode']
        );
        foreach ($this->subExtensionList as $subExtension) {
            $subExtension->load($container, $config);
        }
        $this->createService(
            $container,
            'initializer.behat_subscriber',
            BehatContextSubscriberInitializer::class,
            [new Reference('event_dispatcher')],
            ['context.initializer']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->subExtensionList as $subExtension) {
            $subExtension->process($container);
        }
    }
}
