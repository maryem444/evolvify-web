<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjetRepository::class)]
class Projet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_projet = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', enumType: StatutProjet::class)]
    private ?StatutProjet $status = null;

    #[ORM\Column(name: "end_date", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(name: "starter_at", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $starterAt = null;


    #[ORM\Column(length: 255)]
    private ?string $abbreviation = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $uploaded_files = null;
    

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

    public function setEndDate(\DateTimeInterface $end_date): static
    {
        $this->endDate = $end_date;

        return $this;
    }

    public function getStarterAt(): ?\DateTimeInterface
    {
        return $this->starterAt;
    }

    public function setStarterAt(\DateTimeInterface $starter_at): static
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
