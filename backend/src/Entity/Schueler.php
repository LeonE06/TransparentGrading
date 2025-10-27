<?php

namespace App\Entity;

use App\Repository\SchuelerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchuelerRepository::class)]
#[ORM\Table(name: "Schueler")]
class Schueler
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $vorname;

    #[ORM\Column(length: 100)]
    private string $nachname;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $geburtsdatum = null;

    #[ORM\ManyToOne(targetEntity: Klassen::class, inversedBy: "schueler")]
    #[ORM\JoinColumn(name: "klasse_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Klassen $klasse = null;

    public function getId(): ?int { return $this->id; }

    public function getVorname(): string { return $this->vorname; }
    public function setVorname(string $vorname): self { $this->vorname = $vorname; return $this; }

    public function getNachname(): string { return $this->nachname; }
    public function setNachname(string $nachname): self { $this->nachname = $nachname; return $this; }

    public function getGeburtsdatum(): ?\DateTimeInterface { return $this->geburtsdatum; }
    public function setGeburtsdatum(?\DateTimeInterface $datum): self { $this->geburtsdatum = $datum; return $this; }

    public function getKlasse(): ?Klassen { return $this->klasse; }
    public function setKlasse(?Klassen $klasse): self { $this->klasse = $klasse; return $this; }
}
