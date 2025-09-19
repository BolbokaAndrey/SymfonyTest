<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: 'role')]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $code; // e.g., ROLE_ADMIN

    #[ORM\Column(length: 255)]
    private string $nameRu; // e.g., Администратор

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getNameRu(): string
    {
        return $this->nameRu;
    }

    public function setNameRu(string $nameRu): self
    {
        $this->nameRu = $nameRu;
        return $this;
    }
}
