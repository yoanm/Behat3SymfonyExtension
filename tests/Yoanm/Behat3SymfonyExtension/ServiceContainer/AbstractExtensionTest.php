<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Prophecy\Argument;
use Prophecy\Argument\Token;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

abstract class AbstractExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $key
     *
     * @return string
     */
    protected function buildContainerId($key)
    {
        return sprintf(
            '%s.%s',
            Behat3SymfonyExtension::BASE_CONTAINER_ID,
            $key
        );
    }

    /**
     * @param ObjectProphecy|ContainerBuilder $container
     * @param string                          $id
     * @param string                          $class
     * @param array|null                      $expectedDefinitionArgumentList
     * @param array                           $tagList
     * @param array|null                      $expectedCallArgumentList
     * @param Token\TokenInterface|null      $factory
     * @param bool                            $called
     */
    protected function assertCreateServiceCalls(
        ObjectProphecy $container,
        $id,
        $class,
        array $expectedDefinitionArgumentList = null,
        $tagList = [],
        array $expectedCallArgumentList = null,
        Token\TokenInterface $factoryAssertion = null,
        $called = true
    ) {

        $globalSetDefinitionArgumentCheckList = [
            Argument::type(Definition::class),
            Argument::which('getClass', $class)
        ];
        if (null !== $expectedDefinitionArgumentList) {
            $globalSetDefinitionArgumentCheckList[] = Argument::that(
                function (Definition $definition) use ($expectedDefinitionArgumentList) {
                    $argList = $definition->getArguments();
                    foreach ($expectedDefinitionArgumentList as $key => $expected) {
                        if (isset($argList[$key])) {
                            $actual = $argList[$key];
                            if ($expected instanceof Token\TokenInterface) {
                                return $expected->scoreArgument($actual);
                            } elseif ($expected == $actual) {
                                return true;
                            }
                        } else {
                            // Argument expected but not set
                            return false;
                        }
                    }
                    return true;
                }
            );
        }
        if (null !== $expectedCallArgumentList) {
            $globalSetDefinitionArgumentCheckList[] = Argument::that(
                function (Definition $definition) use ($expectedCallArgumentList) {
                    $callList = $definition->getMethodCalls();
                    foreach ($expectedCallArgumentList as $key => $data) {
                        if ($callList[$key][0] !== $data[0]) {
                            return false;
                        }
                        if (isset($data[1])) {
                            foreach ($data[1] as $subKey => $expected) {
                                $actual = $callList[$key][1][$subKey];
                                if ($expected instanceof Token\TokenInterface) {
                                    if ($expected->scoreArgument($actual) === false) {
                                        return false;
                                    }
                                } elseif ($expected != $actual) {
                                    return false;
                                }
                            }
                        }
                    }
                    return true;
                }
            );
        }

        foreach ($tagList as $tag) {
            // Assert tag is defined
            $globalSetDefinitionArgumentCheckList[] = Argument::that(
                function (Definition $definition) use ($tag) {
                    return $definition->hasTag($tag);
                }
            );
        }

        if (null !== $factoryAssertion) {
            $globalSetDefinitionArgumentCheckList[] = $factoryAssertion;
        }

        /** @var MethodProphecy $setDefinitionProphecy */
        $setDefinitionProphecy = $container->setDefinition(
            $this->buildContainerId($id),
            new Token\LogicalAndToken($globalSetDefinitionArgumentCheckList)
        );
        if (true === $called) {
            $setDefinitionProphecy->shouldHaveBeenCalled();
        } else {
            $setDefinitionProphecy->shouldNotHaveBeenCalled();
        }
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
