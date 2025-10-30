<?php

namespace App\Controller;

use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/students')]
class StudentController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $query = $request->query->get('search', '');
        $repo = $em->getRepository(Schueler::class);

        $students = $repo->createQueryBuilder('s')
            ->where('s.vorname LIKE :q OR s.nachname LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $json = $serializer->serialize($students, 'json', ['groups' => ['student_read']]);
        return new JsonResponse($json, 200, [], true);
    }
}
