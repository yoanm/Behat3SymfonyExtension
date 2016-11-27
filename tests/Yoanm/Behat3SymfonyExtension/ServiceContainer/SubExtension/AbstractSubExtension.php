<?php
namespace Tests\Yoanm\Behat3SymfonyExtension\ServiceContainer\SubExtension;

use Prophecy\Argument;
use Prophecy\Argument\Token;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

abstract class AbstractSubExtension extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $key
     *
     * @return string
     */
    protected function getContainerParamOrServiceId($key)
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
     * @param bool                            $called
     */
    protected function assertCreateServiceCalls(
        ObjectProphecy $container,
        $id,
        $class,
        array $expectedDefinitionArgumentList = null,
        $tagList = array(),
        array $expectedCallArgumentList = null,
        $called = true
    ) {

        $globalSetDefinitionArgumentCheckList = array(
            Argument::type(Definition::class),
            Argument::which('getClass', $class)
        );
        if (null !== $expectedDefinitionArgumentList) {
            $globalSetDefinitionArgumentCheckList[] = Argument::that(
                function (Definition $definition) use ($expectedDefinitionArgumentList) {
                    $argList = $definition->getArguments();
                    foreach ($expectedDefinitionArgumentList as $key => $expected) {
                        $actual = $argList[$key];
                        if ($expected instanceof Token\TokenInterface) {
                            if ($expected->scoreArgument($actual) === false) {
                                return false;
                            }
                        } elseif ($expected != $actual) {
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

        /** @var MethodProphecy $setDefinitionProphecy */
        $setDefinitionProphecy = $container->setDefinition(
            $this->getContainerParamOrServiceId($id),
            new Token\LogicalAndToken($globalSetDefinitionArgumentCheckList)
        );
        if ($called) {
            $setDefinitionProphecy->shouldHaveBeenCalled();
        } else {
            $setDefinitionProphecy->shouldNotHaveBeenCalled();
        }
    }

    /**
     * @param string $serviceId
     *
     * @return Argument\Token\TokenInterface
     */
    protected function getReferenceAssertion($serviceId)
    {
        return Argument::allOf(
            Argument::type(Reference::class),
            Argument::which('__toString', $serviceId)
        );
    }
}