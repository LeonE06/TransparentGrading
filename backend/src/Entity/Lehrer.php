<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\LehrerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LehrerRepository::class)]
#[ORM\Table(name: "Lehrer")]
class Lehrer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = NULL;

    #[ORM\Column(length: 100)]
    private string $vorname;

    #[ORM\Column(length: 100)]
    private string $nachname;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $fach = NULL;

    #[ORM\ManyToOne(targetEntity: Microsoft365User::class)]
    #[ORM\JoinColumn(name: "ms365usr_id", referencedColumnName: "id", nullable: false)]
    private ?Microsoft365User $ms365usr = NULL;

    #[ORM\OneToMany(mappedBy: 'lehrer', targetEntity: Kurse::class)]
    private Collection $kurse;

    // ----- Getter & Setter -----

    public function __construct()
{
    $this->kurse = new ArrayCollection();
}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVorname(): string
    {
        return $this->vorname;
    }

    public function setVorname(string $vorname): self
    {
        $this->vorname = $vorname;
        return $this;
    }

    public function getNachname(): string
    {
        return $this->nachname;
    }

    public function setNachname(string $nachname): self
    {
        $this->nachname = $nachname;
        return $this;
    }

    public function getFach(): ?string
    {
        return $this->fach;
    }

    public function setFach(?string $fach): self
    {
        $this->fach = $fach;
        return $this;
    }

    public function getMs365usr(): ?Microsoft365User
    {
        return $this->ms365usr;
    }

    public function setMs365usr(?Microsoft365User $ms365usr): self
    {
        $this->ms365usr = $ms365usr;
        return $this;
    }

public function getKurse(): Collection
{
    return $this->kurse;
}
}
