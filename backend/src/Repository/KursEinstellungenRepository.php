<?php

namespace App\Repository;

use App\Entity\KursEinstellungen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KursEinstellungenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KursEinstellungen::class);
    }
}
