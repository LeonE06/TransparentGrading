<?php

namespace App\Controller;

use App\Entity\Einstellungen;
use App\Service\CurrentSchuelerResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SchuelerStatusController extends AbstractController
{
    private CurrentSchuelerResolver $resolver;

    // ✅ HIER – das ist der Constructor
    public function __construct(CurrentSchuelerResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    #[Route('/api/schueler/status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $schueler = $this->resolver->get();

        if (!$schueler) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $needsBirthdate = $schueler->getGeburtsdatum() === null;
        $needsParentEmail = false;

        if (!$needsBirthdate) {
            $today = new \DateTime();
            $age = $today->diff($schueler->getGeburtsdatum())->y;

            if ($age < 18) {
                $einstellungen = $schueler->getEinstellungen();
                $needsParentEmail = !$einstellungen || !$einstellungen->getElternemail();
            }
        }

        return new JsonResponse([
            'needsBirthdate' => $needsBirthdate,
            'needsParentEmail' => $needsParentEmail,
        ]);
    }

    #[Route('/api/schueler/geburtsdatum', methods: ['POST'])]
    public function saveBirthdate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $schueler = $this->resolver->get();

        if (!$schueler) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['geburtsdatum'])) {
            return new JsonResponse(['error' => 'Geburtsdatum fehlt'], 400);
        }

        $schueler->setGeburtsdatum(new \DateTime($data['geburtsdatum']));
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/schueler/elternemail', methods: ['POST'])]
    public function saveParentEmail(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $schueler = $this->resolver->get();

        if (!$schueler) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Ungültige E-Mail'], 400);
        }

        $settings = $schueler->getEinstellungen();
        if (!$settings) {
            $settings = new Einstellungen();
            $settings->setSchueler($schueler);
            $em->persist($settings);
        }

        $settings->setElternemail($email);
        $settings->setElternaktivierung(true);

        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
