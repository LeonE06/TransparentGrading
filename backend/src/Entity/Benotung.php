<?php

namespace App\Entity;

use App\Repository\BenotungRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BenotungRepository::class)]
#[ORM\Table(name: "Benotung")]
class Benotung
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'schueler_id', referencedColumnName: 'id', nullable: false)]
    private ?Schueler $schueler = NULL;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'fach_id', referencedColumnName: 'id', nullable: false)]
    private ?Faecher $fach = NULL;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'lehrer_id', referencedColumnName: 'id', nullable: false)]
    private ?Lehrer $lehrer = NULL;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $datum;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'typ', referencedColumnName: 'id', nullable: false)]
    private ?Benotungsarten $typ = NULL;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $note;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $kommentar = NULL;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFach(): ?Faecher
    {
        return $this->fach;
    }

    public function setFach(?Faecher $fach): self
    {
        $this->fach = $fach;
        return $this;
    }

    public function getLehrer(): ?Lehrer
    {
        return $this->lehrer;
    }

    public function setLehrer(?Lehrer $lehrer): self
    {
        $this->lehrer = $lehrer;
        return $this;
    }

    public function getDatum(): \DateTimeInterface
    {
        return $this->datum;
    }

    public function setDatum(\DateTimeInterface $datum): self
    {
        $this->datum = $datum;
        return $this;
    }

    public function getTyp(): ?Benotungsarten
    {
        return $this->typ;
    }

    public function setTyp(?Benotungsarten $typ): self
    {
        $this->typ = $typ;
        return $this;
    }

    public function getNote(): float
    {
        return $this->note;
    }

    public function setNote(float $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getKommentar(): ?string
    {
        return $this->kommentar;
    }

    public function setKommentar(?string $kommentar): self
    {
        $this->kommentar = $kommentar;
        return $this;
    }
}
