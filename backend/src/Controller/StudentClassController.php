<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/schueler')]
class StudentClassController extends AbstractController
{
    #[Route('/faecher', methods: ['GET'])]
    public function getFaecher(EntityManagerInterface $em): JsonResponse
    {
        $schuelerId = 1; // TODO: Auth später

        $rows = $em->getConnection()->executeQuery("
            SELECT 
                k.id AS kurs_id,
                f.name AS fach_name,
                c.name AS klasse_name,
                COALESCE(ke.benachrichtigung, 1) AS notif_enabled,
                COALESCE(ke.sichtbar, 1) AS sichtbar
            FROM Kurs_Schueler ks
            JOIN Kurse k ON ks.kurs_id = k.id
            JOIN Faecher f ON f.id = k.fach_id
            LEFT JOIN Klassen c ON c.id = k.klasse_id
            LEFT JOIN Kurs_Einstellungen ke
                ON ke.kurs_id = k.id AND ke.schueler_id = ks.schueler_id
            WHERE ks.schueler_id = :sid
            ORDER BY f.name ASC
        ", [
            'sid' => $schuelerId
        ])->fetchAllAssociative();

        return new JsonResponse($rows);
    }


    #[Route('/faecher/{kursId}/toggle-visibility', methods: ['PUT'])]
    public function toggleVisibility(int $kursId, EntityManagerInterface $em): JsonResponse
    {
        $schuelerId = 1;

        $sql = "
            INSERT INTO Kurs_Einstellungen (schueler_id, kurs_id, sichtbar)
            VALUES (:sid, :kid, 0)
            ON DUPLICATE KEY UPDATE sichtbar = NOT sichtbar
        ";

        $em->getConnection()->executeStatement($sql, [
            'sid' => $schuelerId,
            'kid' => $kursId
        ]);

        return new JsonResponse(['status' => 'ok']);
    }


    #[Route('/faecher/{kursId}/toggle-notif', methods: ['PUT'])]
    public function toggleNotif(int $kursId, EntityManagerInterface $em): JsonResponse
    {
        $schuelerId = 1;

        $sql = "
            INSERT INTO Kurs_Einstellungen (schueler_id, kurs_id, benachrichtigung)
            VALUES (:sid, :kid, 0)
            ON DUPLICATE KEY UPDATE benachrichtigung = NOT benachrichtigung
        ";

        $em->getConnection()->executeStatement($sql, [
            'sid' => $schuelerId,
            'kid' => $kursId
        ]);

        return new JsonResponse(['status' => 'ok']);
    }
}
