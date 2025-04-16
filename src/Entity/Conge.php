<?php

namespace App\Entity;

use App\Repository\CongeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CongeRepository::class)]
#[ORM\Table(name: "congé")]
class Conge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_conge", type: Types::INTEGER)]
    private ?int $idConge = null;

    #[ORM\Column(name: "leave_start", type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est obligatoire")]
    #[Assert\Type("\DateTimeInterface", message: "La date de début doit être une date valide")]
    private ?\DateTimeInterface $leaveStart = null;

    #[ORM\Column(name: "leave_end", type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire")]
    #[Assert\Type("\DateTimeInterface", message: "La date de fin doit être une date valide")]
    private ?\DateTimeInterface $leaveEnd = null;

    #[ORM\Column(name: "number_of_days", type: Types::INTEGER)]
    #[Assert\NotBlank(message: "Le nombre de jours est obligatoire")]
    #[Assert\Positive(message: "Le nombre de jours doit être positif")]
    private ?int $numberOfDays = null;

    #[ORM\Column(name: "status", type: "string")]
    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    private ?string $statusString = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "id_employe", referencedColumnName: "id_employe", nullable: false)]
    #[Assert\NotNull(message: "L'employé est obligatoire")]
    private ?User $employe = null;

    #[ORM\Column(name: "type", type: "string")]
    #[Assert\NotBlank(message: "Le type de congé est obligatoire")]
    private ?string $typeString = null;

    #[ORM\Column(name: "reason", type: "string")]
    #[Assert\NotBlank(message: "La raison du congé est obligatoire")]
    private ?string $reasonString = null;

    #[ORM\Column(name: "description", type: Types::STRING, length: 255, nullable: true)]
    private ?string $description = null;

    /**
     * Validate that leave end date is after leave start date
     */
    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context, $payload)
    {
        if ($this->leaveStart && $this->leaveEnd) {
            if ($this->leaveEnd < $this->leaveStart) {
                $context->buildViolation('La date de fin doit être postérieure à la date de début')
                    ->atPath('leaveEnd')
                    ->addViolation();
            }
        }
    }
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

    // Modifier les méthodes pour l'employé
    public function getEmploye(): ?User
    {
        return $this->employe;
    }

    public function setEmploye(?User $employe): static
    {
        $this->employe = $employe;
        return $this;
    }

    // Pour assurer la compatibilité avec le code existant
    public function getIdEmploye(): ?int
    {
        return $this->employe ? $this->employe->getId() : null;
    }

    public function setIdEmploye(?int $idEmploye): static
    {
        // Cette méthode n'est plus utilisée directement, mais gardée pour compatibilité
        // Dans le contrôleur, on utilisera setEmploye() à la place
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