<?php

namespace App\Entity;

use App\Repository\AufgabenBewertungRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AufgabenBewertungRepository::class)]
#[ORM\Table(name: 'Aufgaben_Bewertung')]
class AufgabenBewertung
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = NULL;

    #[ORM\ManyToOne(inversedBy: 'bewertungen')]
    #[ORM\JoinColumn(name: 'aufgabe_id', referencedColumnName: 'id', nullable: false)]
    private ?Aufgaben $aufgabe = NULL;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'schueler_id', referencedColumnName: 'id', nullable: false)]
    private ?Schueler $schueler = NULL;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'lehrer_id', referencedColumnName: 'id', nullable: false)]
    private ?Lehrer $lehrer = NULL;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $note;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $kommentar = NULL;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAufgabe(): ?Aufgaben
    {
        return $this->aufgabe;
    }

    public function setAufgabe(?Aufgaben $aufgabe): self
    {
        $this->aufgabe = $aufgabe;
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

    public function getLehrer(): ?Lehrer
    {
        return $this->lehrer;
    }

    public function setLehrer(?Lehrer $lehrer): self
    {
        $this->lehrer = $lehrer;
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
