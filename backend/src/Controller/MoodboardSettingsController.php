<?php

namespace App\Controller;

use App\Entity\Einstellungen;
use App\Entity\Microsoft365User;
use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MoodboardSettingsController extends AbstractController
{
    private function resolveSchueler(EntityManagerInterface $em): ?Schueler
    {
        $user = $this->getUser();
        if (!$user) return null;

        // gleiches Pattern wie in deinem MoodController:
        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$ms365User) return null;

        return $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);
    }

    private function getOrCreateSettings(EntityManagerInterface $em, int $schuelerId): Einstellungen
    {
        // PK == schueler_id
        $settings = $em->getRepository(Einstellungen::class)->find($schuelerId);

        if (!$settings) {
            $settings = new Einstellungen();
            $settings->setId($schuelerId);
            $settings->setMoodBenachrichtigung(true); // default an
            $em->persist($settings);
            $em->flush();
        }

        return $settings;
    }

    #[Route('/api/moodboard/settings', name: 'api_moodboard_settings_get', methods: ['GET'])]
    public function getMoodboardSettings(EntityManagerInterface $em): JsonResponse
    {
        $schueler = $this->resolveSchueler($em);
        if (!$schueler) {
            return $this->json(['error' => 'Nicht authentifiziert oder Schüler nicht gefunden'], 401);
        }

        $settings = $this->getOrCreateSettings($em, $schueler->getId());

        return $this->json([
            'mood_benachrichtigung' => $settings->isMoodBenachrichtigung()
        ]);
    }

    #[Route('/api/moodboard/settings', name: 'api_moodboard_settings_put', methods: ['PUT'])]
    public function putMoodboardSettings(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $schueler = $this->resolveSchueler($em);
        if (!$schueler) {
            return $this->json(['error' => 'Nicht authentifiziert oder Schüler nicht gefunden'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !array_key_exists('mood_benachrichtigung', $data)) {
            return $this->json(['error' => 'Feld "mood_benachrichtigung" fehlt'], 400);
        }

        $enabled = filter_var($data['mood_benachrichtigung'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($enabled === null) {
            return $this->json(['error' => '"mood_benachrichtigung" muss true/false sein'], 400);
        }

        $settings = $this->getOrCreateSettings($em, $schueler->getId());
        $settings->setMoodBenachrichtigung($enabled);
        $em->flush();

        return $this->json([
            'status' => 'ok',
            'mood_benachrichtigung' => $settings->isMoodBenachrichtigung()
        ]);
    }
}
