<?php
namespace Yoanm\TestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function testAction(Request $request)
    {
        return new Response(sprintf(
            'value is "%s"',
            $request->query->getAlnum('value')
        ));
    }

    public function exceptionAction()
    {
        throw new \Exception('my_exception');
    }
}
