<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
// User stores ROLE CODES in JSON field `roles`. Authorization uses these codes directly.

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (count($roles) === 0) {
            $roles[] = 'ROLE_USER';
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * Checks if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        $normalized = strtoupper($role);
        if (!str_starts_with($normalized, 'ROLE_')) {
            $normalized = 'ROLE_' . $normalized;
        }
        return in_array($normalized, $this->getRoles(), true);
    }

    /**
     * Adds a role to the user if not present. Accepts either 'ROLE_X' or 'X'.
     */
    public function addRole(string $role): self
    {
        $normalized = strtoupper($role);
        if (!str_starts_with($normalized, 'ROLE_')) {
            $normalized = 'ROLE_' . $normalized;
        }
        $roles = $this->roles;
        if (!in_array($normalized, $roles, true)) {
            $roles[] = $normalized;
            $this->roles = $roles;
        }
        return $this;
    }

    /**
     * Removes a role from the user if present. Accepts either 'ROLE_X' or 'X'.
     */
    public function removeRole(string $role): self
    {
        $normalized = strtoupper($role);
        if (!str_starts_with($normalized, 'ROLE_')) {
            $normalized = 'ROLE_' . $normalized;
        }
        $this->roles = array_values(array_filter($this->roles, fn ($r) => $r !== $normalized));
        return $this;
    }

    public function eraseCredentials(): void
    {
        //
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
