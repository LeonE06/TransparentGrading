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

        // Prüfe ob Schüler über 18 ist
        $isUeber18 = false;
        if ($user->getGeburtsdatum()) {
            $today = new \DateTime();
            $geburtsdatum = $user->getGeburtsdatum();
            $age = $today->diff($geburtsdatum)->y;
            $isUeber18 = $age >= 18;
        }

        return new JsonResponse([
            'light_darkmode' => $settings ? $settings->getLightDarkmode() : null,
            'benachrichtigungen' => $settings ? $settings->getBenachrichtigungen() : null,
            'elternemail' => $settings ? $settings->getElternemail() : null,
            'elternaktivierung' => $settings ? $settings->getElternaktivierung() : null,
            'isUeber18' => $isUeber18
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

        // Prüfe ob Schüler über 18 ist
        $isUeber18 = false;
        if ($user->getGeburtsdatum()) {
            $today = new \DateTime();
            $geburtsdatum = $user->getGeburtsdatum();
            $age = $today->diff($geburtsdatum)->y;
            $isUeber18 = $age >= 18;
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

        // Eltern-Email und Aktivierung (nur wenn über 18)
        if ($isUeber18) {
            if (array_key_exists('elternemail', $data)) {
                $email = $data['elternemail'];
                // Email-Validierung: null oder leer = erlaubt, sonst muss es eine gültige Email sein
                if ($email === null || $email === '') {
                    $settings->setElternemail(null);
                } elseif (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $settings->setElternemail($email);
                } else {
                    return new JsonResponse(['error' => 'Ungültige E-Mail-Adresse'], 400);
                }
            }

            if (array_key_exists('elternaktivierung', $data)) {
                $settings->setElternaktivierung($data['elternaktivierung']);
            }
        } else {
            // Wenn unter 18, keine Änderungen erlauben
            if (array_key_exists('elternemail', $data) || array_key_exists('elternaktivierung', $data)) {
                return new JsonResponse(['error' => 'Nur Schüler über 18 können die Eltern-E-Mail bearbeiten'], 403);
            }
        }

        $em->flush();

        return new JsonResponse([
            'light_darkmode' => $settings->getLightDarkmode(),
            'benachrichtigungen' => $settings->getBenachrichtigungen(),
            'elternemail' => $settings->getElternemail(),
            'elternaktivierung' => $settings->getElternaktivierung(),
            'isUeber18' => $isUeber18
        ], 200);
    }
}