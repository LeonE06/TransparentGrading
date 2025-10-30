<?php

namespace App\Repository;

use App\Entity\AufgabenBewertung;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AufgabenBewertungRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AufgabenBewertung::class);
    }
}
