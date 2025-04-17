<?php

namespace App\Entity;

use App\Entity\AbsenceStatus;
use App\Repository\AbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
class Absence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'id_absence')]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
private ?AbsenceStatus $status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'integer', name: 'id_employe')]
    private ?int $idEmploye = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?AbsenceStatus
    {
        return $this->status;
    }

    public function setStatus(AbsenceStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getIdEmploye(): ?int
    {
        return $this->idEmploye;
    }

    public function setIdEmploye(int $idEmploye): static
    {
        $this->idEmploye = $idEmploye;

        return $this;
    }
}