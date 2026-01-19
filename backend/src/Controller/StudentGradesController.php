<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class StudentGradesController extends AbstractController
{
    #[Route('/schueler/faecher/{kursId}/noten', methods: ['GET'])]
    public function getNoten(
        int $kursId,
        EntityManagerInterface $em
    ): JsonResponse {

        // ------------------------------------------------
        // 🔧 DEBUG-MODUS (temporär: arbeitet ohne Login!)
        // ------------------------------------------------
        $DEBUG = true;  // <-- später auf false setzen, wenn MS Login fertig!

        if ($DEBUG) {
            // Nutze Schüler mit ID = 1
            $schueler = $em->getRepository(\App\Entity\Schueler::class)->find(1);

            if (!$schueler) {
                return new JsonResponse([
                    'error' => 'DEBUG FEHLER: Schüler mit ID 1 existiert nicht.'
                ], 500);
            }
        } else {
            // ------------------------------------------------
            // 🔒 ORIGINALER MODE (Microsoft Login)
            // ------------------------------------------------
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'Not authorized'], 401);
            }

            $schueler = $em->getRepository(\App\Entity\Schueler::class)
                           ->findOneBy(['ms365User' => $user->getId()]);

            if (!$schueler) {
                return new JsonResponse(['error' => 'Schüler nicht gefunden'], 404);
            }
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
            'schueler_notenstand' => $schuelerDurchschnitt ? round($schuelerDurchschnitt, 2) : null,
            'klassenschnitt' => $klassenDurchschnitt ? round($klassenDurchschnitt, 2) : null,
        ]);
    }
}
