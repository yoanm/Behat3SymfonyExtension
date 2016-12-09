<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Prophecy\Argument;
use Prophecy\Argument\Token;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

abstract class AbstractExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param ObjectProphecy|ContainerBuilder $container
     * @param string                          $fileName
     */
    protected function assertContainerAddResourceCalls(ObjectProphecy $container, $fileName)
    {
        $filePath = realpath(sprintf(
            '%s/%s/%s',
            __DIR__,
            '../../../../src/Yoanm/Behat3SymfonyExtension/Resources/config',
            $fileName
        ));
        $container->addResource(Argument::which('getResource', $filePath))
            ->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @param ObjectProphecy|ContainerBuilder $container
     * @param string                          $filePath
     */
    protected function assertSetContainerParameterCalls(ObjectProphecy $container, $key, $value)
    {
        $container->setParameter($key, $value)
            ->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @param string $serviceId
     *
     * @return Token\TokenInterface
     */
    protected function getReferenceAssertion($serviceId)
    {
        return Argument::allOf(
            Argument::type(Reference::class),
            Argument::which('__toString', $serviceId)
        );
    }

    /**
     * @param string $factoryServiceId
     * @param string $methodName
     *
     * @return Token\TokenInterface
     */
    protected function getFactoryServiceAssertion($factoryServiceId, $methodName)
    {
        return Argument::that(function (Definition $definition) use ($factoryServiceId, $methodName) {
            $factory = $definition->getFactory();
            $assertion = Argument::allOf(
                Argument::type('array'),
                // Check reference
                Argument::withEntry(
                    '0',
                    Argument::allOf(
                        Argument::type(Reference::class),
                        Argument::which('__toString', $factoryServiceId)
                    )
                ),
                // Check method name
                Argument::withEntry('1', $methodName)
            );
            return $assertion->scoreArgument($factory) === false ? false : true;
        });
    }
}
