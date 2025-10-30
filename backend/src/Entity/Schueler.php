<?php

namespace App\Entity;

use App\Repository\SchuelerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SchuelerRepository::class)]
class Schueler
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['class_read', 'student_read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['class_read', 'student_read'])]
    private ?string $vorname = null;

    #[ORM\Column(length: 100)]
    #[Groups(['class_read', 'student_read'])]
    private ?string $nachname = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeInterface $geburtsdatum = null;

    #[ORM\ManyToOne(targetEntity: Klassen::class, inversedBy: 'schueler')]
    #[ORM\JoinColumn(name: "klasse_id", referencedColumnName: "id", nullable: true)]
    private ?Klassen $klasse = null;

    public function getId(): ?int { return $this->id; }
    public function getVorname(): ?string { return $this->vorname; }
    public function setVorname(string $vorname): self { $this->vorname = $vorname; return $this; }

    public function getNachname(): ?string { return $this->nachname; }
    public function setNachname(string $nachname): self { $this->nachname = $nachname; return $this; }

    public function getKlasse(): ?Klassen { return $this->klasse; }
    public function setKlasse(?Klassen $klasse): self { $this->klasse = $klasse; return $this; }
}
