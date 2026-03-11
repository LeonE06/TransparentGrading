<?php

namespace App\Repository;

use App\Entity\Faecher;
use App\Entity\Lehrer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FaecherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faecher::class);
    }

    /**
     * @return Faecher[]
     */
    public function findForLehrer(Lehrer $lehrer): array
    {
        return $this->createQueryBuilder('f')
            ->select('DISTINCT f')
            ->innerJoin('f.lehrerFaecher', 'lf')
            ->andWhere('lf.lehrer = :lehrer')
            ->setParameter('lehrer', $lehrer)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
