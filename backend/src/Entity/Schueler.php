<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\SchuelerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SchuelerRepository::class)]
#[ORM\Table(name: "Schueler")]
class Schueler
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['class_read', 'student_read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['class_read', 'student_read'])]
    private ?string $vorname = null;

    #[ORM\Column(length: 100)]
    #[Groups(['class_read', 'student_read'])]
    private ?string $nachname = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $geburtsdatum = null;

    #[ORM\ManyToOne(targetEntity: Klassen::class, inversedBy: 'schueler')]
    #[ORM\JoinColumn(name: "klasse_id", referencedColumnName: "id", nullable: true)]
    private ?Klassen $klasse = null;

    #[ORM\OneToOne(mappedBy: 'schueler', targetEntity: Einstellungen::class)]
    private ?Einstellungen $einstellungen = null;


    #[ORM\OneToMany(mappedBy: 'zielSchueler', targetEntity: Nachrichten::class)]
    private Collection $empfangeneNachrichten;

    #[ORM\OneToMany(mappedBy: 'schueler', targetEntity: NachrichtenStatus::class)]
    private Collection $nachrichtenStatus;

    #[ORM\OneToMany(mappedBy: 'schueler', targetEntity: KursSchueler::class)]
    private Collection $kursSchueler;

    #[ORM\OneToMany(mappedBy: 'schueler', targetEntity: KursEinstellungen::class)]
    private Collection $kursEinstellungen;

    public function __construct()
    {
        $this->empfangeneNachrichten = new ArrayCollection();
        $this->nachrichtenStatus = new ArrayCollection();
        $this->kursSchueler = new ArrayCollection();
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
            $kursEinstellung->setSchueler($this);
        }

        return $this;
    }

    public function removeKursEinstellung(KursEinstellungen $kursEinstellung): self
    {
        if ($this->kursEinstellungen->removeElement($kursEinstellung)) {
            if ($kursEinstellung->getSchueler() === $this) {
                $kursEinstellung->setSchueler(null);
            }
        }

        return $this;
    }
        public function getEmpfangeneNachrichten(): Collection
        {
            return $this->empfangeneNachrichten;
        }

        public function getNachrichtenStatus(): Collection
        {
            return $this->nachrichtenStatus;
        }

        public function getKursSchueler(): Collection
        {
            return $this->kursSchueler;
        }

    public function getId(): ?int { return $this->id; }
    public function getVorname(): ?string { return $this->vorname; }
    public function setVorname(string $vorname): self { $this->vorname = $vorname; return $this; }

    public function getNachname(): ?string { return $this->nachname; }
    public function setNachname(string $nachname): self { $this->nachname = $nachname; return $this; }

    public function getKlasse(): ?Klassen { return $this->klasse; }
    public function setKlasse(?Klassen $klasse): self { $this->klasse = $klasse; return $this; }

public function getEinstellungen(): ?Einstellungen
{
    return $this->einstellungen;
}

public function setEinstellungen(?Einstellungen $einstellungen): self
{
    $this->einstellungen = $einstellungen;
    return $this;
}
}
