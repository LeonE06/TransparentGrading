<?php

namespace App\Controller;

use App\Entity\Einstellungen;
use App\Entity\KursEinstellungen;
use App\Entity\Schueler;
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

        // Hole den Schueler aus der DB basierend auf der Email
        $em = $doctrine->getManager();
        $ms365User = $em->getRepository(\App\Entity\Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$ms365User) {
            return new JsonResponse(['error' => 'Benutzer nicht gefunden'], 404);
        }

        $schueler = $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);

        if (!$schueler) {
            return new JsonResponse(['error' => 'Schüler nicht gefunden'], 404);
        }

        $repo = $doctrine->getRepository(Einstellungen::class);
        $settings = $repo->findOneBy(['schueler' => $schueler]);

        // Prüfe ob Schüler über 18 ist
        $isUeber18 = false;
        if ($schueler->getGeburtsdatum()) {
            $today = new \DateTime();
            $geburtsdatum = $schueler->getGeburtsdatum();
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

        // Hole den Schueler aus der DB basierend auf der Email
        $em = $doctrine->getManager();
        $ms365User = $em->getRepository(\App\Entity\Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$ms365User) {
            return new JsonResponse(['error' => 'Benutzer nicht gefunden'], 404);
        }

        $schueler = $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);

        if (!$schueler) {
            return new JsonResponse(['error' => 'Schüler nicht gefunden'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $repo = $doctrine->getRepository(Einstellungen::class);
        $kursEinstellungenRepo = $doctrine->getRepository(KursEinstellungen::class);

        $settings = $repo->find($schueler->getId()); // ← Direkt über die ID finden, da die ID = Schueler-ID ist

        if (!$settings) {
            $settings = new Einstellungen();
            $settings->setSchueler($schueler); // Das setzt automatisch die ID
            $em->persist($settings);
        }

        // Prüfe ob Schüler über 18 ist
        $isUeber18 = false;
        if ($schueler->getGeburtsdatum()) {
            $today = new \DateTime();
            $geburtsdatum = $schueler->getGeburtsdatum();
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
            $kursSchueler = $schueler->getKursSchueler();

            foreach ($kursSchueler as $ks) {
                $kurs = $ks->getKurs();

                $kursEinstellung = $kursEinstellungenRepo->findOneBy([
                    'schueler' => $schueler,
                    'kurs' => $kurs
                ]);

                if (!$kursEinstellung) {
                    $kursEinstellung = new KursEinstellungen();
                    $kursEinstellung->setSchueler($schueler);
                    $kursEinstellung->setKurs($kurs);
                    $em->persist($kursEinstellung);
                }

                $kursEinstellung->setBenachrichtigung($globalValue);
            }
        }

        // Eltern-Email und Aktivierung (auch wenn unter 18 - für die erste Eingabe)
        if (array_key_exists('elternemail', $data)) {
            $email = $data['elternemail'];
            if ($email === null || $email === '') {
                $settings->setElternemail(null);
            } elseif (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $settings->setElternemail($email);
            } else {
                return new JsonResponse(['error' => 'Ungültige E-Mail-Adresse'], 400);
            }
        }

        // Eltern-Aktivierung nur wenn über 18
        if ($isUeber18 && array_key_exists('elternaktivierung', $data)) {
            $settings->setElternaktivierung($data['elternaktivierung']);
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Fehler beim Speichern',
                'details' => $e->getMessage()
            ], 500);
        }

        return new JsonResponse([
            'light_darkmode' => $settings->getLightDarkmode(),
            'benachrichtigungen' => $settings->getBenachrichtigungen(),
            'elternemail' => $settings->getElternemail(),
            'elternaktivierung' => $settings->getElternaktivierung(),
            'isUeber18' => $isUeber18
        ], 200);
    }
}