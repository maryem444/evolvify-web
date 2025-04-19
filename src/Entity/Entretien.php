<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Entity\StatusEntretien;
#[ORM\Entity]
class Entretien
{
   
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $idListOffre = null;

    #[ORM\Column]
    private int $idCondidate;

    #[ORM\Column]
    private int $idOffre;

    #[ORM\Column(type: "string", enumType: StatusEntretien::class)]
    private StatusEntretien $status = StatusEntretien::EN_COURS;
    

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePostulation = null;


    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nomCandidat = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $prenomCandidat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titreOffre = null;





    // Getters et Setters
    public function getIdListOffre(): ?int
    {
        return $this->idListOffre;
    }

    public function getIdCondidate(): int
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
