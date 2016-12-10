<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

use Symfony\Component\HttpFoundation\Response;

class AfterRequestEvent extends Event
{
    /** @var Response */
    private $request;
    /** @var string */
    private $name;

    /**
     * @param Response $request
     */
    public function __construct(Response $request)
    {
        $this->request = $request;
    }

    /**
     * @return Response
     */
    public function getResponse()
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
