<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Tache;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjetRepository::class)]
class Projet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_projet;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du projet est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $name = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    private ?string $description = '';

    #[ORM\Column(type: 'string', enumType: StatutProjet::class)]
    private ?StatutProjet $status;

    #[ORM\Column(name: "end_date", type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: "La date du projet est obligatoire.")]
    #[Assert\GreaterThan(propertyPath: "starterAt", message: "⚠️ La date de fin doit être postérieure à la date de début.")]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(name: "starter_at", type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: "La date du projet est obligatoire.")]
    #[Assert\LessThan(propertyPath: "endDate", message: "⚠️ La date de début doit être antérieure à la date de fin.")]
    private ?\DateTimeInterface $starterAt = null;


    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'abréviation est obligatoire.")]
    #[Assert\Length(
        max: 10,
        maxMessage: "L'abréviation ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $abbreviation = '';

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\File(
        maxSize: "5M",
        mimeTypes: ["application/pdf", "image/jpeg", "image/png"],
        mimeTypesMessage: "Le fichier doit être un PDF, une image JPG ou PNG."
    )]
    private ?string $uploaded_files = null;


    #[ORM\OneToMany(mappedBy: "projet", targetEntity: Tache::class, cascade: ["persist", "remove"])]
    private Collection $taches;

    public function __construct()
    {
        $this->taches = new ArrayCollection();
    }

    public function getTaches(): Collection
    {
        return $this->taches;
    }

    public function addTache(Tache $tache): static
    {
        if (!$this->taches->contains($tache)) {
            $this->taches[] = $tache;
            $tache->setProjet($this);
        }
        return $this;
    }

    public function removeTache(Tache $tache): static
    {
        if ($this->taches->removeElement($tache)) {
            if ($tache->getProjet() === $this) {
                $tache->setProjet(null);
            }
        }
        return $this;
    }



    public function getId(): ?int
    {
        return $this->id_projet;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?StatutProjet
    {
        return $this->status;
    }

    public function setStatus(StatutProjet $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $end_date): static
    {
        $this->endDate = $end_date;
        return $this;
    }

    public function getStarterAt(): ?\DateTimeInterface
    {
        return $this->starterAt;
    }

    public function setStarterAt(?\DateTimeInterface $starter_at): static
    {
        $this->starterAt = $starter_at;
        return $this;
    }


    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation): static
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    public function getUploadedFiles(): ?string
    {
        return $this->uploaded_files;
    }

    public function setUploadedFiles(?string $uploaded_files): static
    {
        $this->uploaded_files = $uploaded_files;
        return $this;
    }

    public function __toString(): string
    {
        // Retourne un format lisible pour l'objet Projet
        return sprintf(
            "%s (%s)", // Par exemple : "Nom du projet (Statut)"
            $this->name,
            $this->status ? (string) $this->status : 'Statut inconnu' // Assurez-vous que le statut est bien converti en chaîne
        );
    }
}
