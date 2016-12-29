<?php
namespace Technical\Unit\Yoanm\Behat3SymfonyExtension\ServiceContainer;

use Prophecy\Argument;
use Prophecy\Argument\Token;
use Yoanm\Behat3SymfonyExtension\ServiceContainer\Behat3SymfonyExtension;

class Behat3SymfonyExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var Behat3SymfonyExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->extension = new Behat3SymfonyExtension();
    }

    public function testGetConfigKey()
    {
        $this->assertSame(
            'behat3_symfony',
            $this->extension->getConfigKey()
        );
    }
}
