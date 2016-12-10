<?php
namespace Yoanm\Behat3SymfonyExtension\Event;

use Symfony\Component\HttpFoundation\Request;

class BeforeRequestEvent extends AbstractEvent
{
    /** @var Request */
    private $request;

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
}
