<?php

namespace App\Entity;

use App\Repository\MoodRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MoodRepository::class)]
#[ORM\Table(name: 'mood')]
class Mood
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['mood:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Schueler::class, inversedBy: 'moods')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Schueler $schueler = null;

    #[ORM\Column(length: 20)]
    #[Groups(['mood:read'])]
    private ?string $mood = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['mood:read'])]
    private ?string $note = null;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    #[Groups(['mood:read'])]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
    }

    public function getId(): ?int { return $this->id; }

    public function getSchueler(): ?Schueler { return $this->schueler; }
    public function setSchueler(?Schueler $schueler): self { $this->schueler = $schueler; return $this; }

    public function getMood(): ?string { return $this->mood; }
    public function setMood(?string $mood): self { $this->mood = $mood; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
