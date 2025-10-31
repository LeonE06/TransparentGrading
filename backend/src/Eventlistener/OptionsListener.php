<?php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Response;

class OptionsListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->isMethod('OPTIONS')) {
            // Leere Antwort erzeugen
            $response = new Response();
            $response->setStatusCode(204);

            // Diese Zeile ist der Trick:
            // Browser dürfen CORS trotzdem prüfen – Nelmio fügt Header hinzu
            $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin', '*'));
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

            $event->setResponse($response);
        }
    }
}
