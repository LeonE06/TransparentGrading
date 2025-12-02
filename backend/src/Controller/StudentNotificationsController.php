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
        // DEBUG: Immer Schüler 1
        $schuelerId = 1;

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
            LEFT JOIN Kurse ku ON ku.id = n.kurs_id
            LEFT JOIN Faecher f ON f.id = ku.fach_id
            LEFT JOIN Klassen k ON k.id = ku.klasse_id
            INNER JOIN Nachrichten_Status ns 
                ON ns.nachricht_id = n.id 
               AND ns.schueler_id = :sid
            ORDER BY n.erstellt_am DESC
        ";

        $data = $em->getConnection()->executeQuery($sql, [
            'sid' => $schuelerId
        ])->fetchAllAssociative();

        return new JsonResponse($data);
    }
}
