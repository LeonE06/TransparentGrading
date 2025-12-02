<?php

namespace App\Controller;

use App\Entity\Kurse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Controller für Schüler-bezogene Ansichten
 * z. B. Anzeige der Fächer, in denen der Schüler eingeschrieben ist.
 */
#[Route('/api')]
class StudentClassController extends AbstractController
{
    /**
     * Gibt alle Fächer (Kurse) mit Fach- und Klassenbezug zurück.
     * GET /api/schueler/faecher
     */
    #[Route('/schueler/faecher', name: 'api_schueler_faecher', methods: ['GET'])]
public function getFaecher(EntityManagerInterface $em): JsonResponse
{
    // DEBUG: Schüler 1 verwenden
    $DEBUG = true;

    if ($DEBUG) {
        $schuelerId = 1;
    } else {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authorized'], 401);
        }

        $schueler = $em->getRepository(Schueler::class)->findOneBy(['ms365usr' => $user->getId()]);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Schüler nicht gefunden'], 404);
        }

        $schuelerId = $schueler->getId();
    }

    // Flacher Query ohne Entities
    $rows = $em->getConnection()->executeQuery("
        SELECT 
            k.id AS kurs_id,
            k.name AS kurs_name,
            f.id AS fach_id,
            f.name AS fach_name,
            c.name AS klasse_name
        FROM Kurs_Schueler ks
        JOIN Kurse k ON ks.kurs_id = k.id
        JOIN Faecher f ON f.id = k.fach_id
        LEFT JOIN Klassen c ON c.id = k.klasse_id
        WHERE ks.schueler_id = :sid
    ", [
        'sid' => $schuelerId
    ])->fetchAllAssociative();

    return new JsonResponse($rows);
}


    /**
     * Gibt ein bestimmtes Fach (Kurs) mit Details zurück.
     * GET /api/schueler/faecher/{id}
     */
    #[Route('/faecher/{id}', name: 'api_schueler_fach_detail', methods: ['GET'])]
    public function getFachDetail(
        int $id,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $kurs = $em->getRepository(Kurse::class)
            ->createQueryBuilder('k')
            ->leftJoin('k.fach', 'f')
            ->leftJoin('k.klasse', 'c')
            ->addSelect('f', 'c')
            ->where('k.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$kurs) {
            return new JsonResponse(['error' => 'Fach nicht gefunden'], 404);
        }

        $json = $serializer->serialize($kurs, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($json, 200, [], true);
    }
}
