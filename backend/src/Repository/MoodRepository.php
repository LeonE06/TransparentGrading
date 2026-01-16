<?php

namespace App\Repository;

use App\Entity\Mood;
use App\Entity\Schueler;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mood>
 */
class MoodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mood::class);
    }

    /** @return Mood[] */
    public function findLatestBySchueler(Schueler $schueler, int $limit = 60): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.schueler = :s')
            ->setParameter('s', $schueler)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
