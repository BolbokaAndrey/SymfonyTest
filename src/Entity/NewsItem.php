<?php

namespace App\Entity;

use App\Repository\NewsItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: NewsItemRepository::class)]
class NewsItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $activeAt = null;

    #[ORM\Column(length: 50, nullable: false)]
    private ?string $status = 'draft';

    #[ORM\OneToMany(mappedBy: 'newsItem', targetEntity: PropertyValue::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $propertyValues;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->propertyValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getActiveAt(): ?\DateTimeImmutable
    {
        return $this->activeAt;
    }

    public function setActiveAt(?\DateTimeImmutable $activeAt): static
    {
        $this->activeAt = $activeAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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
            $propertyValue->setNewsItem($this);
        }
        return $this;
    }

    public function removePropertyValue(PropertyValue $propertyValue): static
    {
        if ($this->propertyValues->removeElement($propertyValue)) {
            if ($propertyValue->getNewsItem() === $this) {
                $propertyValue->setNewsItem(null);
            }
        }
        return $this;
    }

    /**
     * Get property value by property definition
     */
    public function getPropertyValue(PropertyDefinition $propertyDefinition): ?PropertyValue
    {
        foreach ($this->propertyValues as $propertyValue) {
            if ($propertyValue->getPropertyDefinition() === $propertyDefinition) {
                return $propertyValue;
            }
        }
        return null;
    }

    /**
     * Get all property values as array with property names as keys
     */
    public function getPropertiesAsArray(): array
    {
        $properties = [];
        foreach ($this->propertyValues as $propertyValue) {
            $definition = $propertyValue->getPropertyDefinition();
            if ($definition && $definition->isActive()) {
                $name = $definition->getName();
                if ($definition->isMultiple()) {
                    $properties[$name] = $propertyValue->getValue();
                } else {
                    $properties[$name] = $propertyValue->getSingleValue();
                }
            }
        }
        return $properties;
    }

    /**
     * Set property value
     */
    public function setPropertyValue(PropertyDefinition $propertyDefinition, $value): static
    {
        $propertyValue = $this->getPropertyValue($propertyDefinition);

        if ($propertyValue === null) {
            $propertyValue = new PropertyValue();
            $propertyValue->setPropertyDefinition($propertyDefinition);
            $propertyValue->setNewsItem($this);
            $this->addPropertyValue($propertyValue);
        }

        if ($propertyDefinition->isMultiple()) {
            $propertyValue->setValue((array) $value);
        } else {
            $propertyValue->setSingleValue($value);
        }

        return $this;
    }

    /**
     * Update timestamp before persist
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
