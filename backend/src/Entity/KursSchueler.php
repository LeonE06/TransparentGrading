<?php

namespace App\Entity;

use App\Repository\KursSchuelerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KursSchuelerRepository::class)]
#[ORM\Table(name: 'Kurs_Schueler')]
class KursSchueler
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'kursSchueler')]
    #[ORM\JoinColumn(name: 'kurs_id', referencedColumnName: 'id')]
    private ?Kurse $kurs = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'kursSchueler')]
    #[ORM\JoinColumn(name: 'schueler_id', referencedColumnName: 'id')]
    private ?Schueler $schueler = null;

    public function getKurs(): ?Kurse
    {
        return $this->kurs;
    }

    public function setKurs(?Kurse $kurs): self
    {
        $this->kurs = $kurs;
        return $this;
    }

    public function getSchueler(): ?Schueler
    {
        return $this->schueler;
    }

    public function setSchueler(?Schueler $schueler): self
    {
        $this->schueler = $schueler;
        return $this;
    }
}
