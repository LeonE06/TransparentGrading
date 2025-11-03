<?php

namespace App\Controller;

use App\Entity\Einstellungen;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SettingsController extends AbstractController
{
    #[Route('/api/settings', name: 'api_get_settings', methods: ['GET'])]
    public function getSettings(ManagerRegistry $doctrine): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $repo = $doctrine->getRepository(Einstellungen::class);
        $settings = $repo->findOneBy(['schueler' => $user]);

        $value = $settings ? $settings->getLightDarkmode() : null;

        return new JsonResponse(['light_darkmode' => $value], 200);
    }

    #[Route('/api/settings', name: 'api_put_settings', methods: ['PUT'])]
    public function putSettings(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!array_key_exists('light_darkmode', $data)) {
            return new JsonResponse(['error' => 'Missing field'], 400);
        }

        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Einstellungen::class);
        $settings = $repo->findOneBy(['schueler' => $user]);

        if (!$settings) {
            $settings = new Einstellungen();
            // Einstellungen entity uses Schueler as primary relation — setSchueler exists?
            $settings->setSchueler($user);
            $em->persist($settings);
        }

        $settings->setLightDarkmode($data['light_darkmode']);
        $em->flush();

        return new JsonResponse(['light_darkmode' => $settings->getLightDarkmode()], 200);
    }
}