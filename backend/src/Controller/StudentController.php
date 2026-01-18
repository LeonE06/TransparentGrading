<?php

namespace App\Controller;

use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/students', name: 'api_students_')]
class StudentController extends AbstractController
{
    /**
     * 🔹 GET /api/students/view?page=1&limit=20
     * -> zieht Daten aus der DB-View mit Pagination
     */
    #[Route('/view', name: 'view', methods: ['GET', 'OPTIONS'])]
    public function getStudentsView(Connection $connection, Request $request): JsonResponse
    {
        // Preflight abfangen
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 204);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 20));
        $offset = ($page - 1) * $limit;

        $students = $connection->fetchAllAssociative(
            'SELECT * FROM view_schueler_uebersicht LIMIT :limit OFFSET :offset',
            ['limit' => $limit, 'offset' => $offset],
            ['limit' => \PDO::PARAM_INT, 'offset' => \PDO::PARAM_INT]
        );

        $total = $connection->fetchOne('SELECT COUNT(*) FROM view_schueler_uebersicht');

        return $this->json([
            'data' => $students,
            'page' => $page,
            'limit' => $limit,
            'total' => (int) $total,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    /**
     * 🔹 GET /api/students
     * (Alternative Variante über Doctrine, ohne View)
     */
    #[Route('', name: 'list', methods: ['GET'])]
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
            'groups' => ['student_read'],
        ]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * 🔹 GET /api/students/{id}
     * -> gibt einen einzelnen Schüler zurück
     */
    #[Route('/{id<\d+>}', name: 'one', methods: ['GET'])]
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
            'groups' => ['student_read'],
        ]);

        return new JsonResponse($json, 200, [], true);
    }
    /**
     * 🔹 DELETE /api/students/{id}
     * -> deleted einen einzelnen schüler
     */
    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function deleteStudent(int $id, EntityManagerInterface $em): JsonResponse
    {
        $student = $em->getRepository(Schueler::class)->find($id);

        if (!$student) {
            return new JsonResponse(['error' => 'Schüler*in nicht gefunden.'], 404);
        }

        $em->remove($student);
        $em->flush();

        return new JsonResponse(['message' => 'Schüler*in gelöscht.'], 200);
    }

    /**
     * 🔹 PUT /api/students/{id}
     * -> updadet einen einzelnen Schüler
     */

    #[Route('/{id<\d+>}', name: 'update_student', methods: ['PUT'])]
    public function updateStudent(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $student = $em->getRepository(Schueler::class)->find($id);

        if (!$student) {
            return new JsonResponse(['error' => 'Schüler*in nicht gefunden.'], 404);
        }

        $data = json_decode($request->getContent(), true);


        // Geburtsdatum aktualisieren (falls vorhanden)
        if (isset($data['geburtsdatum'])) {
            try {
                $geburtsdatum = new \DateTime($data['geburtsdatum']);
                $student->setGeburtsdatum($geburtsdatum);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Ungültiges Geburtsdatum.'], 400);
            }
        }

        // Elternemail aktualisieren (falls vorhanden)
        if (isset($data['email'])) {
            try {
                $email = $data['email'];
                $student->getEinstellungen()->setElternemail($email);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Ungültige Emailadresse.'], 400);
            }
        }
        

        // Klasse aktualisieren (falls vorhanden) - OPTIONAL
        if (isset($data['klasse'])) {
            $klasse = $em->getRepository(\App\Entity\Klassen::class)->findOneBy(['name' => $data['klasse']]);
            if (!$klasse) {
                return new JsonResponse(['error' => 'Klasse nicht gefunden.'], 404);
            }
            $student->setKlasse($klasse);
        }

        $em->flush();

        return new JsonResponse(['message' => 'Schüler*in erfolgreich aktualisiert.']);
    }


}
