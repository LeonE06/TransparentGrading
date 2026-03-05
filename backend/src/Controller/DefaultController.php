<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class DefaultController
{
    #[Route('/', name: 'api_root')]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'message' => 'TransparentGrading API laeuft',
        ]);
    }

    #[Route('/mail-test')]
    public function mailTest(MailerInterface $mailer)
    {
        $mail = (new Email())
            ->from('1033@htl.rennweg.at')
            ->to('lara@ehart.eu')
            ->subject('Test')
            ->text('Test Mail');

        $mailer->send($mail);

        return new Response('Mail gesendet');
    }
}
