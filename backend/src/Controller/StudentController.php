<?php

namespace App\Controller;

use App\Repository\SchuelerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/students')]
class StudentController extends AbstractController
{
    /**
     * 🔹 GET /api/students?search=...
     * Sucht Schüler nach Vor- oder Nachnamen.
     */
    #[Route('', name: 'api_students_search', methods: ['GET'])]
    public function search(
        Request $request,
        SchuelerRepository $repo,
        SerializerInterface $serializer
    ): JsonResponse {
        $search = trim($request->query->get('search', ''));

        // Nur ab 2 Zeichen suchen
        if (strlen($search) < 2) {
            return new JsonResponse([], 200);
        }

        // Suche nach Vorname oder Nachname
        $students = $repo->createQueryBuilder('s')
            ->where('LOWER(s.vorname) LIKE LOWER(:term)')
            ->orWhere('LOWER(s.nachname) LIKE LOWER(:term)')
            ->setParameter('term', '%' . $search . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Nur benötigte Felder serialisieren
        $json = $serializer->serialize($students, 'json', [
            'groups' => ['class_read', 'student_read']
        ]);

        return new JsonResponse($json, 200, [], true);
    }
}
