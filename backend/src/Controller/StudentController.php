<?php

namespace App\Controller;

use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/students')]
class StudentController extends AbstractController
{
    /**
     * 🔹 GET /api/students
     * Gibt alle Schüler*innen zurück, optional gefiltert nach einem Suchbegriff.
     * Beispiel: /api/students?search=leon
     */
    #[Route('', name: 'get_students', methods: ['GET'])]
    public function getAll(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $search = strtolower(trim($request->query->get('search', '')));

        $repo = $em->getRepository(Schueler::class);
        $qb = $repo->createQueryBuilder('s');

        if ($search !== '') {
            $qb->where('LOWER(s.vorname) LIKE :term OR LOWER(s.nachname) LIKE :term')
               ->setParameter('term', '%' . $search . '%');
        }

        $students = $qb
            ->orderBy('s.nachname', 'ASC')
            ->addOrderBy('s.vorname', 'ASC')
            ->getQuery()
            ->getResult();

        $json = $serializer->serialize($students, 'json', [
            'groups' => ['student_read']
        ]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * 🔹 GET /api/students/{id}
     * Gibt die Daten eines einzelnen Schülers zurück.
     */
    #[Route('/{id}', name: 'get_student', methods: ['GET'])]
    public function getOne(
        int $id,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $student = $em->getRepository(Schueler::class)->find($id);

        if (!$student) {
            return new JsonResponse(['error' => 'Schüler*in nicht gefunden.'], 404);
        }

        $json = $serializer->serialize($student, 'json', [
            'groups' => ['student_read']
        ]);

        return new JsonResponse($json, 200, [], true);
    }
}
