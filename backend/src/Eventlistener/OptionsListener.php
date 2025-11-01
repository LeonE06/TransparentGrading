<?php
namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'kernel.request', method: 'onKernelRequest', priority: 255)]
class OptionsListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->isMethod('OPTIONS')) {
            $response = new Response();
            $response->setStatusCode(204);

            // Falls Nelmio oder Proxy nichts gesetzt hat, CORS-Header selbst ergänzen
            $origin = $request->headers->get('Origin', '*');
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

            $event->setResponse($response);
        }
    }
}
