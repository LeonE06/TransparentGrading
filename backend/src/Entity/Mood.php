<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "mood")]
class Mood
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Schueler::class)]
    #[ORM\JoinColumn(name: "schueler_id", referencedColumnName: "id", nullable: false)]
    private ?Schueler $schueler = null;

    #[ORM\Column(type: "string", length: 20)]
    private ?string $mood = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(name: "created_at", type: "datetime")]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ─── Getter / Setter ─────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchueler(): ?Schueler
    {
        return $this->schueler;
    }

    public function setSchueler(Schueler $schueler): self
    {
        $this->schueler = $schueler;
        return $this;
    }

    public function getMood(): ?string
    {
        return $this->mood;
    }

    public function setMood(string $mood): self
    {
        $this->mood = $mood;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
