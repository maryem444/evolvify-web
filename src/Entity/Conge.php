<?php

namespace App\Entity;

use App\Repository\CongeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CongeRepository::class)]
#[ORM\Table(name: "congé")] // Explicitly specify the table name with accent
class Conge // Remove the accent from the PHP class name
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_conge", type: Types::INTEGER)]
    private ?int $idConge = null;

    #[ORM\Column(name: "leave_start", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $leaveStart = null;

    #[ORM\Column(name: "leave_end", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $leaveEnd = null;

    #[ORM\Column(name: "number_of_days", type: Types::INTEGER)]
    private ?int $numberOfDays = null;

    #[ORM\Column(name: "status", type: "string")]
    private ?string $statusString = null;

    #[ORM\Column(name: "id_employe", type: Types::INTEGER)]
    private ?int $idEmploye = null;

    #[ORM\Column(name: "type", type: "string")]
    private ?string $typeString = null;

    #[ORM\Column(name: "reason", type: "string")]
    private ?string $reasonString = null;

    #[ORM\Column(name: "description", type: Types::STRING, length: 255)]
    private ?string $description = null;

    // Getters and Setters with conversion for enums
    public function getId(): ?int
    {
        return $this->idConge;
    }

    public function getLeaveStart(): ?\DateTimeInterface
    {
        return $this->leaveStart;
    }

    public function setLeaveStart(?\DateTimeInterface $leaveStart): static
    {
        $this->leaveStart = $leaveStart;
        return $this;
    }

    public function getLeaveEnd(): ?\DateTimeInterface
    {
        return $this->leaveEnd;
    }

    public function setLeaveEnd(?\DateTimeInterface $leaveEnd): static
    {
        $this->leaveEnd = $leaveEnd;
        return $this;
    }

    public function getNumberOfDays(): ?int
    {
        return $this->numberOfDays;
    }

    public function setNumberOfDays(?int $numberOfDays): static
    {
        $this->numberOfDays = $numberOfDays;
        return $this;
    }

    public function getStatus(): ?CongeStatus
    {
        return $this->statusString ? CongeStatus::tryFrom($this->statusString) : null;
    }

    public function setStatus(?CongeStatus $status): static
    {
        $this->statusString = $status?->value;
        return $this;
    }

    public function getIdEmploye(): ?int
    {
        return $this->idEmploye;
    }

    public function setIdEmploye(?int $idEmploye): static
    {
        $this->idEmploye = $idEmploye;
        return $this;
    }

    public function getType(): ?CongeType
    {
        return $this->typeString ? CongeType::tryFrom($this->typeString) : null;
    }

    public function setType(?CongeType $type): static
    {
        $this->typeString = $type?->value;
        return $this;
    }

    public function getReason(): ?CongeReason
    {
        return $this->reasonString ? CongeReason::tryFrom($this->reasonString) : null;
    }

    public function setReason(?CongeReason $reason): static
    {
        $this->reasonString = $reason?->value;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }
}