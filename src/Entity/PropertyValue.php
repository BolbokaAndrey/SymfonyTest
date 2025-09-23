<?php

namespace App\Entity;

use App\Repository\PropertyValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertyValueRepository::class)]
class PropertyValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    #[ORM\ManyToOne(inversedBy: 'propertyValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PropertyDefinition $propertyDefinition = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPropertyDefinition(): ?PropertyDefinition
    {
        return $this->propertyDefinition;
    }

    public function setPropertyDefinition(?PropertyDefinition $propertyDefinition): static
    {
        $this->propertyDefinition = $propertyDefinition;

        return $this;
    }
}
