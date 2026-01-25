<?php

namespace App\Controller;

use App\Entity\Einstellungen;
use App\Entity\KursEinstellungen;
use App\Entity\Microsoft365User;
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
        $schueler = $this->resolveSchueler($doctrine);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $repo = $doctrine->getRepository(Einstellungen::class);

        // je nach Mapping kannst du auch ->find($schueler) verwenden,
        // findOneBy ist aber safe
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
        $schueler = $this->resolveSchueler($doctrine);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Einstellungen::class);
        $kursEinstellungenRepo = $doctrine->getRepository(KursEinstellungen::class);

        $settings = $repo->findOneBy(['schueler' => $schueler]);

        if (!$settings) {
            $settings = new Einstellungen();
            $settings->setSchueler($schueler);
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
            $globalValue = (bool)$data['benachrichtigungen'];
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

        // Eltern-Email und Aktivierung (nur wenn über 18) -> bleibt wie du willst
        if ($isUeber18) {
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

            if (array_key_exists('elternaktivierung', $data)) {
                $settings->setElternaktivierung((bool)$data['elternaktivierung']);
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

    private function resolveSchueler(ManagerRegistry $doctrine): ?Schueler
    {
        $jwtUser = $this->getUser();
        if (!$jwtUser) return null;

        $em = $doctrine->getManager();

        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $jwtUser->getUserIdentifier()]);

        if (!$ms365User) return null;

        return $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);
    }
}
