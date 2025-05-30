<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[ORM\Table(name: "users")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_employe", type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $firstname;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $lastname;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\Email]
    #[Assert\NotBlank]
    private string $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $password = null;


    #[ORM\Column(name: "profilePhoto", type: 'string', length: 255, nullable: true)]
    private ?string $profilePhoto = null;

    #[ORM\Column(name: "birthdayDate", type: 'date', nullable: true)]
    private ?\DateTimeInterface $birthdayDate = null;

    #[ORM\Column(name: "joiningDate", type: 'date', nullable: true)]
    private ?\DateTimeInterface $joiningDate = null;

    #[ORM\Column(name: "role", type: "string", columnDefinition: "ENUM('RESPONSABLE_RH','CHEF_PROJET','EMPLOYEE','CONDIDAT')")]
    #[Assert\Choice(choices: ['RESPONSABLE_RH', 'CHEF_PROJET', 'EMPLOYEE', 'CONDIDAT'])]
    #[Assert\NotBlank]
    private string $role;

    #[ORM\Column(name: "tt_restants", type: 'integer', nullable: true)]
    private ?int $tt_restants = null;

    #[ORM\Column(name: "conge_restant", type: 'integer', nullable: true)]
    private ?int $conge_restant = null;

    #[ORM\Column(name: "uploaded_cv", type: 'string', length: 255, nullable: true)]
    private ?string $uploaded_cv = null;

    #[ORM\Column(name: "num_tel", type: 'integer', nullable: true)]
    private ?int $num_tel = null;

    #[ORM\Column(name: "gender", type: "string", columnDefinition: "ENUM('HOMME','FEMME')", nullable: true)]
    #[Assert\Choice(choices: ['HOMME', 'FEMME'])]
    private ?string $gender = 'HOMME';

    #[ORM\Column(name: "birthdate_edited", type: 'boolean', nullable: true)]
    private bool $birthdate_edited = false;

    #[ORM\Column(name: "first_login", type: 'boolean', nullable: true)]
    private bool $first_login = true;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Tache::class)]
    private Collection $taches;

    public function __construct()
    {
        $this->taches = new ArrayCollection();
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiration = null;


    // Add this property to your User entity
    #[ORM\Column(name: "facial_data", type: 'text', nullable: true)]
    private ?string $facialData = null;

    // Add the getter and setter methods
    public function getFacialData(): ?string
    {
        return $this->facialData;
    }

    public function setFacialData(?string $facialData): self
    {
        $this->facialData = $facialData;
        return $this;
    }

    // Add a field to track if facial recognition is set up
    #[ORM\Column(name: "facial_auth_enabled", type: 'boolean', nullable: false)]
    private bool $facialAuthEnabled = false;

    public function isFacialAuthEnabled(): bool
    {
        return $this->facialAuthEnabled;
    }

    public function setFacialAuthEnabled(bool $facialAuthEnabled): self
    {
        $this->facialAuthEnabled = $facialAuthEnabled;
        return $this;
    }

    #[ORM\Column(name: "face_image_filename", type: 'string', length: 255, nullable: true)]
private ?string $faceImageFilename = null;

public function getFaceImageFilename(): ?string
{
    return $this->faceImageFilename;
}

public function setFaceImageFilename(?string $faceImageFilename): self
{
    $this->faceImageFilename = $faceImageFilename;
    return $this;
}
    // === GETTERS & SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }
    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }
    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    // UPDATED METHOD: Just return the filename stored in the database
    public function getProfilePhoto(): ?string
    {
        return $this->profilePhoto;
    }

    // Method to get the full URL for display in templates
    public function getProfilePhotoUrl(): ?string
    {
        if (!$this->profilePhoto) {
            return null;
        }

        // The path is already relative to public directory
        return '/' . $this->profilePhoto;
    }

    public function setProfilePhoto(?string $profilePhoto): self
    {
        $this->profilePhoto = $profilePhoto;
        return $this;
    }

    public function getBirthdayDate(): ?\DateTimeInterface
    {
        return $this->birthdayDate;
    }
    public function setBirthdayDate(?\DateTimeInterface $birthdayDate): self
    {
        $this->birthdayDate = $birthdayDate;
        return $this;
    }

    public function getJoiningDate(): ?\DateTimeInterface
    {
        return $this->joiningDate;
    }
    public function setJoiningDate(?\DateTimeInterface $joiningDate): self
    {
        $this->joiningDate = $joiningDate;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }
    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getTtRestants(): ?int
    {
        return $this->tt_restants;
    }
    public function setTtRestants(?int $tt_restants): self
    {
        $this->tt_restants = $tt_restants;
        return $this;
    }

    public function getCongeRestant(): ?int
    {
        return $this->conge_restant;
    }
    public function setCongeRestant(?int $conge_restant): self
    {
        $this->conge_restant = $conge_restant;
        return $this;
    }

    public function getUploadedCv()
    {
        return $this->uploaded_cv;
    }
    public function setUploadedCv($uploaded_cv): self
    {
        $this->uploaded_cv = $uploaded_cv;
        return $this;
    }

    public function getNumTel(): ?int
    {
        return $this->num_tel;
    }
    public function setNumTel(?int $num_tel): self
    {
        $this->num_tel = $num_tel;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }
    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function isBirthdateEdited(): bool
    {
        return $this->birthdate_edited;
    }
    public function setBirthdateEdited(bool $birthdate_edited): self
    {
        $this->birthdate_edited = $birthdate_edited;
        return $this;
    }

    public function isFirstLogin(): bool
    {
        return $this->first_login;
    }
    public function setFirstLogin(bool $first_login): self
    {
        $this->first_login = $first_login;
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getResetTokenExpiration(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiration;
    }

    public function setResetTokenExpiration(?\DateTimeInterface $resetTokenExpiration): self
    {
        $this->resetTokenExpiration = $resetTokenExpiration;

        return $this;
    }

    // === USER INTERFACE METHODS ===

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
    public function getRoles(): array
    {
        // Return primary role based on the database value and include ROLE_USER for basic access
        return ['ROLE_' . strtoupper($this->role), 'ROLE_USER'];
    }

    public function eraseCredentials(): void {}

    // === UTILS ===

    public function __toString(): string
    {
        return sprintf(
            "Employee{id=%d, firstname='%s', lastname='%s', email='%s', role='%s'}",
            $this->id,
            $this->firstname,
            $this->lastname,
            $this->email,
            $this->role
        );
    }

    public function equals(User $employee): bool
    {
        return $this->id === $employee->getId();
    }
}