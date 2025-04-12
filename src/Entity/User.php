<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\EmployeeRepository')]
#[ORM\Table(name: "users")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_employe", type: 'integer')]
    private ?int $id_employe = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $firstname;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $lastname;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $password;

    #[ORM\Column(name: "profilePhoto", type: 'string', length: 255, nullable: true)]
    private ?string $profilePhoto = null;

    #[ORM\Column(name: "birthdayDate", type: 'date', nullable: true)]
    private ?\DateTimeInterface $birthdayDate = null;

    #[ORM\Column(name: "joiningDate", type: 'date', nullable: true)]
    private ?\DateTimeInterface $joiningDate = null;

    #[ORM\Column(name: "role", type: 'string', length: 20)]
    #[Assert\Choice(choices: ['RESPONSABLE_RH', 'CHEF_PROJET', 'EMPLOYEE', 'CONDIDAT'])]
    private string $role;

    #[ORM\Column(name: "tt_restants", type: 'integer', nullable: true)]
    private ?int $tt_restants = null;

    #[ORM\Column(name: "conge_restant", type: 'integer', nullable: true)]
    private ?int $conge_restant = null;

    #[ORM\Column(name: "uploaded_cv", type: 'blob', nullable: true)]
    private $uploaded_cv;

    #[ORM\Column(name: "num_tel", type: 'integer', nullable: true)]
    private ?int $num_tel = null;

    #[ORM\Column(name: "gender", type: 'string', length: 8, nullable: true)]
    #[Assert\Choice(choices: ['HOMME', 'FEMME'])]
    private ?string $gender = null;

    #[ORM\Column(name: "birthdate_edited", type: 'boolean')]
    private bool $birthdate_edited = false;

    #[ORM\Column(name: "first_login", type: 'boolean')]
    private bool $first_login = true;

    // === GETTERS & SETTERS ===

    public function getId(): ?int { return $this->id_employe; }

    public function getFirstname(): string { return $this->firstname; }
    public function setFirstname(string $firstname): self { $this->firstname = $firstname; return $this; }

    public function getLastname(): string { return $this->lastname; }
    public function setLastname(string $lastname): self { $this->lastname = $lastname; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getProfilePhoto(): ?string { return $this->profilePhoto; }
    public function setProfilePhoto(?string $profilePhoto): self { $this->profilePhoto = $profilePhoto; return $this; }

    public function getBirthdayDate(): ?\DateTimeInterface { return $this->birthdayDate; }
    public function setBirthdayDate(?\DateTimeInterface $birthdayDate): self { $this->birthdayDate = $birthdayDate; return $this; }

    public function getJoiningDate(): ?\DateTimeInterface { return $this->joiningDate; }
    public function setJoiningDate(?\DateTimeInterface $joiningDate): self { $this->joiningDate = $joiningDate; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getTtRestants(): ?int { return $this->tt_restants; }
    public function setTtRestants(?int $tt_restants): self { $this->tt_restants = $tt_restants; return $this; }

    public function getCongeRestant(): ?int { return $this->conge_restant; }
    public function setCongeRestant(?int $conge_restant): self { $this->conge_restant = $conge_restant; return $this; }

    public function getUploadedCv() { return $this->uploaded_cv; }
    public function setUploadedCv($uploaded_cv): self { $this->uploaded_cv = $uploaded_cv; return $this; }

    public function getNumTel(): ?int { return $this->num_tel; }
    public function setNumTel(?int $num_tel): self { $this->num_tel = $num_tel; return $this; }

    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): self { $this->gender = $gender; return $this; }

    public function isBirthdateEdited(): bool { return $this->birthdate_edited; }
    public function setBirthdateEdited(bool $birthdate_edited): self { $this->birthdate_edited = $birthdate_edited; return $this; }

    public function isFirstLogin(): bool { return $this->first_login; }
    public function setFirstLogin(bool $first_login): self { $this->first_login = $first_login; return $this; }

    // === USER INTERFACE METHODS ===

    public function getUserIdentifier(): string { return $this->email; }
    public function getRoles(): array { return [$this->role]; }
    public function eraseCredentials(): void {}

    // === UTILS ===

    public function __toString(): string {
        return sprintf("Employee{id=%d, firstname='%s', lastname='%s', email='%s', role='%s'}",
            $this->id_employe, $this->firstname, $this->lastname, $this->email, $this->role
        );
    }

    public function equals(User $employee): bool { return $this->id_employe === $employee->getId(); }
}