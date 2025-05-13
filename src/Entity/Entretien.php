<?php

// src/Entity/Entretien.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Entity\StatusEntretien;

#[ORM\Table(name: "liste_offres")]
#[ORM\Entity(repositoryClass: \App\Repository\EntretienRepository::class)]
class Entretien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_liste_offres")]
    private ?int $idListOffre = null;

    #[ORM\Column(name: "id_condidat")]
    private ?int $idCondidate = null;

    #[ORM\Column(name: "id_offre")]
    private int $idOffre;

    #[ORM\Column(type: "string", enumType: StatusEntretien::class)]
    private StatusEntretien $status = StatusEntretien::EN_COURS;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePostulation = null;

    private ?string $nomCandidat = null;
    private ?string $prenomCandidat = null;
    private ?string $titreOffre = null;

    // Getters et Setters
    public function getIdListOffre(): ?int
    {
        return $this->idListOffre;
    }

    public function setIdListOffre(?int $id): self
    {
        $this->idListOffre = $id;
        return $this;
    }

    public function getIdCondidate(): ?int
    {
        return $this->idCondidate;
    }

    public function setIdCondidate(int $idCondidate): self
    {
        $this->idCondidate = $idCondidate;
        return $this;
    }

    public function getIdOffre(): int
    {
        return $this->idOffre;
    }

    public function setIdOffre(int $idOffre): self
    {
        $this->idOffre = $idOffre;
        return $this;
    }

    public function getStatus(): StatusEntretien
    {
        return $this->status;
    }

    public function setStatus(StatusEntretien $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDatePostulation(): ?\DateTimeInterface
    {
        return $this->datePostulation;
    }

    public function setDatePostulation(?\DateTimeInterface $datePostulation): self
    {
        $this->datePostulation = $datePostulation;
        return $this;
    }

    public function getNomCandidat(): ?string
    {
        return $this->nomCandidat;
    }

    public function setNomCandidat(?string $nomCandidat): self
    {
        $this->nomCandidat = $nomCandidat;
        return $this;
    }

    public function getPrenomCandidat(): ?string
    {
        return $this->prenomCandidat;
    }

    public function setPrenomCandidat(?string $prenomCandidat): self
    {
        $this->prenomCandidat = $prenomCandidat;
        return $this;
    }

    public function getTitreOffre(): ?string
    {
        return $this->titreOffre;
    }

    public function setTitreOffre(?string $titreOffre): self
    {
        $this->titreOffre = $titreOffre;
        return $this;
    }
}
