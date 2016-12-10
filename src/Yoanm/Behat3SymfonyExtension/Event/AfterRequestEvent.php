<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

use Symfony\Component\HttpFoundation\Response;

class AfterRequestEvent extends AbstractEvent
{
    /** @var Response */
    private $request;

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
}
