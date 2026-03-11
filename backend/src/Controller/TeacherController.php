<?php

namespace App\Controller;

use App\Entity\Faecher;
use App\Entity\Lehrer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/teachers', name: 'api_teachers_')]
class TeacherController extends AbstractController
{
    private function syncSubjectAssignments(EntityManagerInterface $em, ?int $teacherId = null): void
    {
        $params = [];
        $sql = "INSERT IGNORE INTO lehrer_fach (leher_id, fach_id)
                SELECT DISTINCT k.lehrer_id, k.fach_id
                FROM Kurse k
                WHERE k.lehrer_id IS NOT NULL
                  AND k.fach_id IS NOT NULL";

        if ($teacherId !== null) {
            $sql .= ' AND k.lehrer_id = :teacherId';
            $params['teacherId'] = $teacherId;
        }

        $em->getConnection()->executeStatement($sql, $params);
    }

    /**
     * 🔹 GET /api/teachers/view
     * Holt alle Lehrer*innen mit ihren Fächern über lehrer_fach.
     */
    #[Route('/view', name: 'view', methods: ['GET'])]
    public function viewTeachers(EntityManagerInterface $em): JsonResponse
    {
        $conn = $em->getConnection();

        try {
            $this->syncSubjectAssignments($em);

            $sql = "SELECT
                        l.id,
                        l.vorname,
                        l.nachname,
                        u.email,
                        GROUP_CONCAT(DISTINCT f.name ORDER BY f.name SEPARATOR ', ') AS faecher
                    FROM Lehrer l
                    LEFT JOIN tbl_Microsoft365_User u ON u.id = l.ms365usr_id
                    LEFT JOIN lehrer_fach lf ON lf.leher_id = l.id
                    LEFT JOIN Faecher f ON f.id = lf.fach_id
                    GROUP BY l.id, l.vorname, l.nachname, u.email
                    ORDER BY l.nachname ASC, l.vorname ASC";
            $result = $conn->executeQuery($sql)->fetchAllAssociative();
            return $this->json($result);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Fehler beim Abrufen der Lehrer*innen',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔹 GET /api/teachers/{id}
     * Holt einen einzelnen Lehrer anhand der ID
     */
    #[Route('/{id<\d+>}', name: 'get', methods: ['GET'])]
    public function getTeacher(int $id, EntityManagerInterface $em): JsonResponse
    {
        $teacher = $em->getRepository(Lehrer::class)->find($id);

        if (!$teacher) {
            return new JsonResponse(['error' => 'Lehrer*in nicht gefunden'], 404);
        }

        $this->syncSubjectAssignments($em, $teacher->getId());
        $subjects = $em->getRepository(Faecher::class)->findForLehrer($teacher);

        return $this->json([
            'id' => $teacher->getId(),
            'vorname' => $teacher->getVorname(),
            'nachname' => $teacher->getNachname(),
            'email' => $teacher->getMs365User()?->getEmail(),
            'faecher' => array_map(static fn (Faecher $fach): array => [
                'id' => $fach->getId(),
                'name' => $fach->getName(),
            ], $subjects),
        ]);
    }

    /**
     * 🔹 DELETE /api/teachers/{id}
     * Löscht einen Lehrer anhand der ID
     */
    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function deleteTeacher(int $id, EntityManagerInterface $em): JsonResponse
    {
        $teacher = $em->getRepository(Lehrer::class)->find($id);

        if (!$teacher) {
            return new JsonResponse(['error' => 'Lehrer*in nicht gefunden'], 404);
        }

        try {
            $em->getConnection()->executeStatement(
                'DELETE FROM lehrer_fach WHERE leher_id = :id',
                ['id' => $id]
            );
            $em->remove($teacher);
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Fehler beim Löschen des Lehrers',
                'details' => $e->getMessage(),
            ], 500);
        }

        return new JsonResponse(['message' => 'Lehrer*in erfolgreich gelöscht']);
    }

}
