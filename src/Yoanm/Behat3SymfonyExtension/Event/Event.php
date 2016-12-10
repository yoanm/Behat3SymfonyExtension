<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

use Symfony\Component\EventDispatcher\Event as BaseEvent;

abstract class Event extends BaseEvent
{
    /** @var string */
    private $name;

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
