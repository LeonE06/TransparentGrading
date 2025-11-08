<?php

namespace App\Controller;

use App\Entity\Einstellungen;
use App\Entity\KursEinstellungen;
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

        return new JsonResponse([
            'light_darkmode' => $settings ? $settings->getLightDarkmode() : null,
            'benachrichtigungen' => $settings ? $settings->getBenachrichtigungen() : null
        ], 200);
    }

    #[Route('/api/settings', name: 'api_put_settings', methods: ['PUT'])]
    public function putSettings(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);

        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Einstellungen::class);
        $kursEinstellungenRepo = $doctrine->getRepository(KursEinstellungen::class);
        
        $settings = $repo->findOneBy(['schueler' => $user]);

        if (!$settings) {
            $settings = new Einstellungen();
            $settings->setSchueler($user);
            $em->persist($settings);
        }

        // Beide Felder können optional sein
        if (array_key_exists('light_darkmode', $data)) {
            $settings->setLightDarkmode($data['light_darkmode']);
        }

        if (array_key_exists('benachrichtigungen', $data)) {
            $globalValue = $data['benachrichtigungen'];
            $settings->setBenachrichtigungen($globalValue);
            
            // Alle KursEinstellungen für diesen Schüler aktualisieren
            // 1. Alle Kurse des Schülers finden (über KursSchueler)
            $kursSchueler = $user->getKursSchueler();
            
            foreach ($kursSchueler as $ks) {
                $kurs = $ks->getKurs();
                
                // 2. Prüfen ob bereits eine KursEinstellung existiert
                $kursEinstellung = $kursEinstellungenRepo->findOneBy([
                    'schueler' => $user,
                    'kurs' => $kurs
                ]);
                
                // 3. Falls nicht vorhanden, neue erstellen
                if (!$kursEinstellung) {
                    $kursEinstellung = new KursEinstellungen();
                    $kursEinstellung->setSchueler($user);
                    $kursEinstellung->setKurs($kurs);
                    $em->persist($kursEinstellung);
                }
                
                // 4. Benachrichtigung auf den globalen Wert setzen
                $kursEinstellung->setBenachrichtigung($globalValue);
            }
        }

        $em->flush();

        return new JsonResponse([
            'light_darkmode' => $settings->getLightDarkmode(),
            'benachrichtigungen' => $settings->getBenachrichtigungen()
        ], 200);
    }
}