<?php

namespace App\Entity;

use App\Repository\NachrichtenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NachrichtenRepository::class)]
class Nachrichten
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'nachrichten')]
    #[ORM\JoinColumn(name: 'kurs_id', referencedColumnName: 'id', nullable: true)]
    private ?Kurse $kurs = null;

    #[ORM\ManyToOne(inversedBy: 'empfangeneNachrichten')]
    #[ORM\JoinColumn(name: 'ziel_schueler_id', referencedColumnName: 'id', nullable: true)]
    private ?Schueler $zielSchueler = null;

    #[ORM\Column(length: 200)]
    private string $titel;

    #[ORM\Column(type: 'text')]
    private string $inhalt;

    #[ORM\Column(type: 'datetime', columnDefinition: 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP')]
    private \DateTimeInterface $erstelltAm;


    #[ORM\OneToMany(mappedBy: 'nachricht', targetEntity: NachrichtenStatus::class, cascade: ['persist', 'remove'])]
    private Collection $status;

    public function __construct()
    {
        $this->status = new ArrayCollection();
        $this->erstelltAm = new \DateTime();
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

    public function getZielSchueler(): ?Schueler
    {
        return $this->zielSchueler;
    }

    public function setZielSchueler(?Schueler $zielSchueler): self
    {
        $this->zielSchueler = $zielSchueler;
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

    public function getInhalt(): string
    {
        return $this->inhalt;
    }

    public function setInhalt(string $inhalt): self
    {
        $this->inhalt = $inhalt;
        return $this;
    }

    public function getErstelltAm(): \DateTimeInterface
    {
        return $this->erstelltAm;
    }

    public function setErstelltAm(\DateTimeInterface $erstelltAm): self
    {
        $this->erstelltAm = $erstelltAm;
        return $this;
    }

    /** @return Collection<int, NachrichtenStatus> */
    public function getStatus(): Collection
    {
        return $this->status;
    }
}
