<?php

namespace App\Entity;

use App\Repository\AufgabenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AufgabenRepository::class)]
class Aufgaben
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = NULL;

    #[ORM\ManyToOne(inversedBy: 'aufgaben')]
    #[ORM\JoinColumn(name: 'kurs_id', referencedColumnName: 'id', nullable: false)]
    private ?Kurse $kurs = NULL;

    #[ORM\Column(length: 200)]
    private string $titel;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $beschreibung = NULL;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $faelligkeit = NULL;

    #[ORM\OneToMany(mappedBy: 'aufgabe', targetEntity: AufgabenBewertung::class)]
    private Collection $bewertungen;

    public function __construct()
    {
        $this->bewertungen = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKurs(): ?Kurse
    {
        return $this->kurs;
    }

    public function setKurs(?Kurse $kurs): self
    {
        $this->kurs = $kurs;
        return $this;
    }

    public function getTitel(): string
    {
        return $this->titel;
    }

    public function setTitel(string $titel): self
    {
        $this->titel = $titel;
        return $this;
    }

    public function getBeschreibung(): ?string
    {
        return $this->beschreibung;
    }

    public function setBeschreibung(?string $beschreibung): self
    {
        $this->beschreibung = $beschreibung;
        return $this;
    }

    public function getFaelligkeit(): ?\DateTimeInterface
    {
        return $this->faelligkeit;
    }

    public function setFaelligkeit(?\DateTimeInterface $faelligkeit): self
    {
        $this->faelligkeit = $faelligkeit;
        return $this;
    }

    /** @return Collection<int, AufgabenBewertung> */
    public function getBewertungen(): Collection
    {
        return $this->bewertungen;
    }
}
