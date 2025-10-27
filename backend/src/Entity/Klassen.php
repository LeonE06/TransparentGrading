<?php

namespace App\Entity;

use App\Repository\KlassenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KlassenRepository::class)]
#[ORM\Table(name: "Klassen")]
class Klassen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 50)]
    private string $name;

    #[ORM\OneToMany(mappedBy: "klasse", targetEntity: Schueler::class)]
    private Collection $schueler;

    public function __construct()
    {
        $this->schueler = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    /**
     * @return Collection<int, Schueler>
     */
    public function getSchueler(): Collection
    {
        return $this->schueler;
    }
}
    