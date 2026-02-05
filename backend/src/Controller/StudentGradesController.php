<?php

namespace App\Controller;

use App\Entity\Kurse;
use App\Entity\Microsoft365User;
use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api')]
class StudentGradesController extends AbstractController
{
    private function getCurrentSchueler(EntityManagerInterface $em): ?Schueler
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        // Microsoft365User über die E-Mail (UserIdentifier) finden
        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$ms365User) {
            return null;
        }

        // Schueler über Relation ms365User finden
        return $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);
    }

    #[Route('/schueler/faecher/{kursId}/noten', methods: ['GET'])]
    public function getNoten(
        int $kursId,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {

        // 🔒 Login erforderlich (Microsoft via Symfony Security)
        $schueler = $this->getCurrentSchueler($em);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Not authorized'], 401);
        }

        // ------------------------------------------------
        // 1) Hole alle Noten zu diesem Fach/Kurs
        // ------------------------------------------------
        $noten = $em->getConnection()->executeQuery("
            SELECT 
                b.id,
                b.datum,
                b.note,
                ba.name AS typ_name,
                ba.gewichtung,
                b.kommentar
            FROM Benotung b
            LEFT JOIN Benotungsarten ba ON ba.id = b.typ
            WHERE b.schueler_id = :sid
              AND b.fach_id = (SELECT fach_id FROM Kurse WHERE id = :kid)
            ORDER BY b.datum ASC
        ", [
            'sid' => $schueler->getId(),
            'kid' => $kursId
        ])->fetchAllAssociative();

        // ------------------------------------------------
        // 2) Berechne Schüler-Durchschnitt
        // ------------------------------------------------
        $schuelerDurchschnitt = $em->getConnection()->executeQuery("
            SELECT AVG(note) AS avg
            FROM Benotung
            WHERE schueler_id = :sid
              AND fach_id = (SELECT fach_id FROM Kurse WHERE id = :kid)
        ", [
            'sid' => $schueler->getId(),
            'kid' => $kursId
        ])->fetchOne();

        // ------------------------------------------------
        // 3) Berechne Klassenschnitt
        // ------------------------------------------------
        $klassenDurchschnitt = $em->getConnection()->executeQuery("
            SELECT AVG(b.note) AS avg
            FROM Benotung b
            WHERE b.fach_id = (SELECT fach_id FROM Kurse WHERE id = :kid)
        ", [
            'kid' => $kursId
        ])->fetchOne();

        // ------------------------------------------------
        // 4) JSON Rückgabe
        // ------------------------------------------------
        return new JsonResponse([
            'noten' => $noten,
            'schueler_notenstand' => $schuelerDurchschnitt !== null ? round((float) $schuelerDurchschnitt, 2) : null,
            'klassenschnitt' => $klassenDurchschnitt !== null ? round((float) $klassenDurchschnitt, 2) : null,
        ]);
    }
}
