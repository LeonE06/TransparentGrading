<?php

namespace App\Controller;

use App\Entity\Lehrer;
use App\Entity\LehrerEinstellungen;
use App\Entity\Microsoft365User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/lehrer', name: 'api_lehrer_settings_')]
class TeacherSettingsController extends AbstractController
{
    private function resolveLehrer(EntityManagerInterface $em): ?Lehrer
    {
        $jwtUser = $this->getUser();
        if (!$jwtUser || !method_exists($jwtUser, 'getUserIdentifier')) {
            return null;
        }

        $email = $jwtUser->getUserIdentifier();
        $m365 = $em->getRepository(Microsoft365User::class)->findOneBy(['email' => $email]);
        if (!$m365) {
            return null;
        }

        return $em->getRepository(Lehrer::class)->findOneBy(['ms365usr' => $m365]);
    }

    #[Route('/einstellungen', name: 'get', methods: ['GET'])]
    public function getSettings(EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $repo = $em->getRepository(LehrerEinstellungen::class);
        $settings = $repo->findOneBy(['lehrer' => $lehrer]);

        return new JsonResponse([
            'sprache' => $settings?->getSprache(),
            'light_darkmode' => $settings?->getLightDarkmode(),
        ]);
    }

    #[Route('/einstellungen', name: 'put', methods: ['PUT'])]
    public function putSettings(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];

        $repo = $em->getRepository(LehrerEinstellungen::class);
        $settings = $repo->findOneBy(['lehrer' => $lehrer]);
        if (!$settings) {
            $settings = new LehrerEinstellungen();
            $settings->setLehrer($lehrer);
            $em->persist($settings);
        }

        if (array_key_exists('sprache', $data)) {
            $settings->setSprache($data['sprache'] !== '' ? $data['sprache'] : null);
        }

        if (array_key_exists('light_darkmode', $data)) {
            $settings->setLightDarkmode($data['light_darkmode'] !== null ? (bool) $data['light_darkmode'] : null);
        }

        $em->flush();

        return new JsonResponse([
            'sprache' => $settings->getSprache(),
            'light_darkmode' => $settings->getLightDarkmode(),
        ]);
    }
}

