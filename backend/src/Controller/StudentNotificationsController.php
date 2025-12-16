<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/schueler')]
class StudentNotificationsController extends AbstractController
{
    #[Route('/nachrichten', methods: ['GET'])]
    public function getNachrichten(EntityManagerInterface $em): JsonResponse
    {
        $schuelerId = 1; // TODO: später aus Auth

        $sql = "
            SELECT 
                n.id,
                n.titel,
                n.inhalt,
                n.erstellt_am,
                f.name AS fach_name,
                k.name AS kurs_name,
                ns.gelesen
        FROM Nachrichten n
        LEFT JOIN Kurse k ON k.id = n.kurs_id
        LEFT JOIN Faecher f ON f.id = k.fach_id
        INNER JOIN Nachrichten_Status ns 
                ON ns.nachricht_id = n.id 
                AND ns.schueler_id = :sid
        LEFT JOIN Kurs_Einstellungen ke
                ON ke.kurs_id = n.kurs_id
                AND ke.schueler_id = :sid
        WHERE COALESCE(ke.benachrichtigung, 1) = 1
        ORDER BY n.erstellt_am DESC
        ";

        $data = $em->getConnection()->executeQuery($sql, [
            'sid' => $schuelerId
        ])->fetchAllAssociative();

        return new JsonResponse($data);
    }

    #[Route('/nachrichten/{id}/lesen', methods: ['PUT'])]
    public function markAsRead(int $id, EntityManagerInterface $em): JsonResponse
    {
        $schuelerId = 1;

        $sql = "
            UPDATE Nachrichten_Status
            SET gelesen = 1
            WHERE schueler_id = :sid AND nachricht_id = :nid
        ";

        $em->getConnection()->executeStatement($sql, [
            'sid' => $schuelerId,
            'nid' => $id,
        ]);

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/nachrichten/{id}/ungelesen', methods: ['PUT'])]
    public function markAsUnread(int $id, EntityManagerInterface $em): JsonResponse
    {
        $schuelerId = 1;

        $sql = "
            UPDATE Nachrichten_Status
            SET gelesen = 0
            WHERE schueler_id = :sid AND nachricht_id = :nid
        ";

        $em->getConnection()->executeStatement($sql, [
            'sid' => $schuelerId,
            'nid' => $id,
        ]);

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/nachrichten/{id}', methods: ['DELETE'])]
    public function deleteNachricht(int $id, EntityManagerInterface $em): JsonResponse
    {
        $schuelerId = 1;

        $sql = "
            DELETE FROM Nachrichten_Status
            WHERE schueler_id = :sid AND nachricht_id = :nid
        ";

        $em->getConnection()->executeStatement($sql, [
            'sid' => $schuelerId,
            'nid' => $id,
        ]);

        return new JsonResponse(['status' => 'ok']);
    }
}
