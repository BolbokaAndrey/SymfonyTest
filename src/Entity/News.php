<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
class News
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $activeAt = null;

    #[ORM\OneToOne(targetEntity: File::class)]
    #[ORM\JoinColumn(name: 'news_picture_id', referencedColumnName: 'id')]
    #[ORM\JoinColumn(nullable: true)]
    private ?File $newsPicture = null;

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

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getActiveAt(): ?\DateTimeInterface
    {
        return $this->activeAt;
    }

    public function setActiveAt(?\DateTimeInterface $activeAt): void
    {
        $this->activeAt = $activeAt;
    }

    public function getNewsPicture(): ?File
    {
        return $this->newsPicture;
    }

    public function setNewsPicture(?File $newsPicture): void
    {
        $this->newsPicture = $newsPicture;
    }
}
