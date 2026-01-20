<?php

namespace App\Entity;

use App\Repository\BenotungsartenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BenotungsartenRepository::class)]
#[ORM\Table(name: "Benotungsarten")]
class Benotungsarten
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    // decimal kommt von Doctrine typischerweise als string zurück -> passt so
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $gewichtung = '0';


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

    public function getGewichtung(): string
    {
        return $this->gewichtung;
    }

    public function setGewichtung(string $gewichtung): self
    {
        $this->gewichtung = $gewichtung;
        return $this;
    }
}
