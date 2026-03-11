<?php

namespace App\Entity;

use App\Repository\LehrerFachRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LehrerFachRepository::class)]
#[ORM\Table(name: 'lehrer_fach')]
class LehrerFach
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'lehrerFaecher')]
    #[ORM\JoinColumn(name: 'leher_id', referencedColumnName: 'id', nullable: false)]
    private ?Lehrer $lehrer = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'lehrerFaecher')]
    #[ORM\JoinColumn(name: 'fach_id', referencedColumnName: 'id', nullable: false)]
    private ?Faecher $fach = null;

    public function getLehrer(): ?Lehrer
    {
        return $this->lehrer;
    }

    public function setLehrer(?Lehrer $lehrer): self
    {
        $this->lehrer = $lehrer;

        return $this;
    }

    public function getFach(): ?Faecher
    {
        return $this->fach;
    }

    public function setFach(?Faecher $fach): self
    {
        $this->fach = $fach;

        return $this;
    }
}
