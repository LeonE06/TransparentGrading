<?php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Response;

class OptionsListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // CORS Preflight? -> Antwort ohne Routing
        if ($request->isMethod('OPTIONS')) {
            $response = new Response();
            $response->setStatusCode(204);
            $event->setResponse($response);
        }
    }
}
