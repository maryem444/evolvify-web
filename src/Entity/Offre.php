<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;
use App\Entity\Status;

#[ORM\Entity]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $idOffre = null;

    
    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $datePublication = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $dateExpiration = null;

    #[ORM\Column(type: 'string', enumType: Status::class)]
    private ?Status $status = Status::ATTEND; 

    public function getIdOffre(): ?int
    {
        return $this->idOffre;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
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

    public function getDatePublication(): ?DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(DateTimeInterface $datePublication): static
    {
        $this->datePublication = $datePublication;
        return $this;
    }

    public function getDateExpiration(): ?DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(DateTimeInterface $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function __toString(): string
    {
        return "Offre: {$this->titre} (" . ($this->status?->value ?? 'Aucun statut') . ")";
    }
    
}
