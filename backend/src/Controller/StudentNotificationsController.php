<?php

namespace App\Controller;

use App\Entity\Microsoft365User;
use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/schueler')]
class StudentNotificationsController extends AbstractController
{
    private function getCurrentSchueler(EntityManagerInterface $em): ?Schueler
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$ms365User) {
            return null;
        }

        return $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);
    }

    #[Route('/nachrichten', methods: ['GET'])]
public function getNachrichten(EntityManagerInterface $em): JsonResponse
{
    $schueler = $this->getCurrentSchueler($em);
    if (!$schueler) {
        return new JsonResponse(['error' => 'Not authorized'], 401);
    }

    $schuelerId = $schueler->getId();

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

    // 🔔 Mood-Erinnerung, wenn heute noch nichts eingetragen wurde
    $hasMoodToday = (bool) $em->getConnection()->fetchOne(
        "SELECT 1 FROM mood 
         WHERE schueler_id = :sid 
           AND DATE(created_at) = CURDATE()
         LIMIT 1",
        ['sid' => $schuelerId]
    );

    if (!$hasMoodToday) {
        array_unshift($data, [
            'id' => 'mood-reminder',
            'titel' => 'Mood eintragen',
            'inhalt' => 'Du hast heute noch keinen Mood eingetragen. Bitte kurz speichern 🙂',
            'erstellt_am' => (new \DateTime())->format('Y-m-d H:i:s'),
            'fach_name' => null,
            'kurs_name' => null,
            'gelesen' => 0,
            'system' => 1
        ]);
    }

    return new JsonResponse($data);
}

    #[Route('/nachrichten/{id}/lesen', methods: ['PUT'])]
    public function markAsRead(int $id, EntityManagerInterface $em): JsonResponse
    {
        $schueler = $this->getCurrentSchueler($em);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Not authorized'], 401);
        }

        $sql = "
            UPDATE Nachrichten_Status
            SET gelesen = 1
            WHERE schueler_id = :sid AND nachricht_id = :nid
        ";

        $em->getConnection()->executeStatement($sql, [
            'sid' => $schueler->getId(),
            'nid' => $id,
        ]);

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/nachrichten/{id}/ungelesen', methods: ['PUT'])]
    public function markAsUnread(int $id, EntityManagerInterface $em): JsonResponse
    {
        $schueler = $this->getCurrentSchueler($em);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Not authorized'], 401);
        }

        $sql = "
            UPDATE Nachrichten_Status
            SET gelesen = 0
            WHERE schueler_id = :sid AND nachricht_id = :nid
        ";

        $em->getConnection()->executeStatement($sql, [
            'sid' => $schueler->getId(),
            'nid' => $id,
        ]);

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/nachrichten/{id}', methods: ['DELETE'])]
    public function deleteNachricht(int $id, EntityManagerInterface $em): JsonResponse
    {
        $schueler = $this->getCurrentSchueler($em);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Not authorized'], 401);
        }

        $sql = "
            DELETE FROM Nachrichten_Status
            WHERE schueler_id = :sid AND nachricht_id = :nid
        ";

        $em->getConnection()->executeStatement($sql, [
            'sid' => $schueler->getId(),
            'nid' => $id,
        ]);

        return new JsonResponse(['status' => 'ok']);
    }

}
