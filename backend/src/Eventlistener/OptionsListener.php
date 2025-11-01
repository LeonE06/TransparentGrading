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
            $response = new Response();
            $response->setStatusCode(204);

            // 👉 Nur setzen, wenn Nelmio nicht aktiv (z. B. auf Render, falls Cache-Probleme)
            if (!$response->headers->has('Access-Control-Allow-Origin')) {
                $origin = $request->headers->get('Origin', '*');
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            }

            $event->setResponse($response);
        }
    }
}
