<?php

namespace App\Entity;

use App\Repository\PropertyDefinitionRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: PropertyDefinitionRepository::class)]
class PropertyDefinition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column]
    private ?bool $required = false;

    #[ORM\Column]
    private ?bool $multiple = false;

    #[ORM\Column]
    private ?int $sortOrder = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $defaultValue = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $active = true;

    #[ORM\OneToMany(mappedBy: 'propertyDefinition', targetEntity: PropertyValue::class, orphanRemoval: true)]
    private Collection $propertyValues;

    public function __construct()
    {
        $this->propertyValues = new ArrayCollection();
    }

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function isRequired(): ?bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): static
    {
        $this->required = $required;
        return $this;
    }

    public function isMultiple(): ?bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getDefaultValue(): ?array
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?array $defaultValue): static
    {
        $this->defaultValue = $defaultValue;
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

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return Collection<int, PropertyValue>
     */
    public function getPropertyValues(): Collection
    {
        return $this->propertyValues;
    }

    public function addPropertyValue(PropertyValue $propertyValue): static
    {
        if (!$this->propertyValues->contains($propertyValue)) {
            $this->propertyValues->add($propertyValue);
            $propertyValue->setPropertyDefinition($this);
        }
        return $this;
    }

    public function removePropertyValue(PropertyValue $propertyValue): static
    {
        if ($this->propertyValues->removeElement($propertyValue)) {
            if ($propertyValue->getPropertyDefinition() === $this) {
                $propertyValue->setPropertyDefinition(null);
            }
        }
        return $this;
    }
}
