<?php

namespace App\Entity;

use App\Repository\PropertyValueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertyValueRepository::class)]
class PropertyValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NewsItem::class, inversedBy: 'propertyValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NewsItem $newsItem = null;

    #[ORM\ManyToOne(targetEntity: PropertyDefinition::class, inversedBy: 'propertyValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PropertyDefinition $propertyDefinition = null;

    #[ORM\Column(type: 'json')]
    private array $value = [];

    #[ORM\Column]
    private ?int $sortOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNewsItem(): ?NewsItem
    {
        return $this->newsItem;
    }

    public function setNewsItem(?NewsItem $newsItem): static
    {
        $this->newsItem = $newsItem;
        return $this;
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

    public function getValue(): array
    {
        return $this->value;
    }

    public function setValue(array $value): static
    {
        $this->value = $value;
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

    /**
     * Get single value if not multiple
     */
    public function getSingleValue()
    {
        return $this->value[0] ?? null;
    }

    /**
     * Set single value if not multiple
     */
    public function setSingleValue($value): static
    {
        $this->value = [$value];
        return $this;
    }
}
