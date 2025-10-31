<?php

namespace App\Entity;

use App\Repository\KlassenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: KlassenRepository::class)]
#[ORM\Table(name: "Klassen")]
class Klassen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['class_read'])]
    private ?int $id = NULL;

    #[ORM\Column(length: 50)]
    #[Groups(['class_read', 'class_write'])]
    private ?string $name = NULL;

    #[ORM\ManyToOne(targetEntity: Lehrer::class)]
    #[ORM\JoinColumn(name: "lehrer_id", referencedColumnName: "id", nullable: true)]
    #[Groups(['class_read'])]
    private ?Lehrer $lehrer = NULL;

    #[ORM\OneToMany(mappedBy: 'klasse', targetEntity: Schueler::class)]
    #[Groups(['class_read'])]
    private Collection $schueler;

    #[ORM\OneToMany(mappedBy: 'klasse', targetEntity: Kurse::class)]
    private Collection $kurse;

    public function __construct()
    {
        $this->schueler = new ArrayCollection();
          $this->kurse = new ArrayCollection();
    }

    // Getters & Setters …

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getLehrer(): ?Lehrer { return $this->lehrer; }
    public function setLehrer(?Lehrer $lehrer): self { $this->lehrer = $lehrer; return $this; }

    /** @return Collection<int, Schueler> */
    public function getSchueler(): Collection { return $this->schueler; }

    public function addSchueler(Schueler $schueler): self {
        if (!$this->schueler->contains($schueler)) {
            $this->schueler->add($schueler);
            $schueler->setKlasse($this);
        }
        return $this;
    }

    public function removeSchueler(Schueler $schueler): self {
        if ($this->schueler->removeElement($schueler)) {
            if ($schueler->getKlasse() === $this) {
                $schueler->setKlasse(null);
            }
        }
        return $this;
    }

    public function getKurse(): Collection
    {
        return $this->kurse;
    }
}
