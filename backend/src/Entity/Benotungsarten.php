<?php

namespace App\Entity;

use App\Repository\BenotungsartenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BenotungsartenRepository::class)]
class Benotungsarten
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = NULL;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => '0'])]
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

    public function getGewichtung(): float
    {
        return $this->gewichtung;
    }

    public function setGewichtung(float $gewichtung): self
    {
        $this->gewichtung = $gewichtung;
        return $this;
    }
}
