<?php

namespace App\Entity;

use App\Repository\KurseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KurseRepository::class)]
#[ORM\Table(name: "Kurse")]
class Kurse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'kurse')]
    #[ORM\JoinColumn(name: 'fach_id', referencedColumnName: 'id', nullable: false)]
    private ?Faecher $fach = null;

    #[ORM\ManyToOne(inversedBy: 'kurse')]
    #[ORM\JoinColumn(name: 'lehrer_id', referencedColumnName: 'id', nullable: false)]
    private ?Lehrer $lehrer = null;

    #[ORM\ManyToOne(inversedBy: 'kurse')]
    #[ORM\JoinColumn(name: 'klasse_id', referencedColumnName: 'id', nullable: true)]
    private ?Klassen $klasse = null;

    #[ORM\OneToMany(mappedBy: 'kurs', targetEntity: Aufgaben::class)]
    private Collection $aufgaben;

    #[ORM\OneToMany(mappedBy: 'kurs', targetEntity: KursSchueler::class)]
    private Collection $kursSchueler;

    #[ORM\OneToMany(mappedBy: 'kurs', targetEntity: Nachrichten::class)]
    private Collection $nachrichten;

    #[ORM\OneToMany(mappedBy: 'kurs', targetEntity: KursEinstellungen::class)]
    private Collection $kursEinstellungen;

    public function __construct()
    {
        $this->aufgaben = new ArrayCollection();
        $this->kursSchueler = new ArrayCollection();
        $this->nachrichten = new ArrayCollection();
        $this->kursEinstellungen = new ArrayCollection();
    }


    public function getKursEinstellungen(): Collection
{
    return $this->kursEinstellungen;
}

public function addKursEinstellung(KursEinstellungen $kursEinstellung): self
{
    if (!$this->kursEinstellungen->contains($kursEinstellung)) {
        $this->kursEinstellungen->add($kursEinstellung);
        $kursEinstellung->setKurs($this);
    }

    return $this;
}

public function removeKursEinstellung(KursEinstellungen $kursEinstellung): self
{
    if ($this->kursEinstellungen->removeElement($kursEinstellung)) {
        if ($kursEinstellung->getKurs() === $this) {
            $kursEinstellung->setKurs(null);
        }
    }

    return $this;
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

    public function getFach(): ?Faecher
    {
        return $this->fach;
    }

    public function setFach(?Faecher $fach): self
    {
        $this->fach = $fach;
        return $this;
    }

    public function getLehrer(): ?Lehrer
    {
        return $this->lehrer;
    }

    public function setLehrer(?Lehrer $lehrer): self
    {
        $this->lehrer = $lehrer;
        return $this;
    }

    public function getKlasse(): ?Klassen
    {
        return $this->klasse;
    }

    public function setKlasse(?Klassen $klasse): self
    {
        $this->klasse = $klasse;
        return $this;
    }

    /** @return Collection<int, Aufgaben> */
    public function getAufgaben(): Collection
    {
        return $this->aufgaben;
    }

    /** @return Collection<int, KursSchueler> */
    public function getKursSchueler(): Collection
    {
        return $this->kursSchueler;
    }

    public function getNachrichten(): Collection
    {
        return $this->nachrichten;
    }
}
