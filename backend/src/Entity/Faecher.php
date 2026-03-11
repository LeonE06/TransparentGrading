<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\FaecherRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaecherRepository::class)]
#[ORM\Table(name: "Faecher")]
class Faecher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = NULL;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\OneToMany(mappedBy: 'fach', targetEntity: Kurse::class)]
    private Collection $kurse;

    #[ORM\OneToMany(mappedBy: 'fach', targetEntity: LehrerFach::class, orphanRemoval: true)]
    private Collection $lehrerFaecher;

    public function __construct()
    {
        $this->kurse = new ArrayCollection();
        $this->lehrerFaecher = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getKurse(): Collection
    {
        return $this->kurse;
    }

    public function getLehrerFaecher(): Collection
    {
        return $this->lehrerFaecher;
    }

    public function addLehrerFach(LehrerFach $lehrerFach): self
    {
        if (!$this->lehrerFaecher->contains($lehrerFach)) {
            $this->lehrerFaecher->add($lehrerFach);
            $lehrerFach->setFach($this);
        }

        return $this;
    }

    public function removeLehrerFach(LehrerFach $lehrerFach): self
    {
        if ($this->lehrerFaecher->removeElement($lehrerFach)) {
            if ($lehrerFach->getFach() === $this) {
                $lehrerFach->setFach(null);
            }
        }

        return $this;
    }
}
