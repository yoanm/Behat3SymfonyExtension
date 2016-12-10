<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

use Symfony\Component\HttpFoundation\Request;

class BeforeRequestEvent extends Event
{
    /** @var Request */
    private $request;
    /** @var string */
    private $name;

    /**
     * @param Request $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

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
