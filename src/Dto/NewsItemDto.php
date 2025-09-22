<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class NewsItemDto
{
    #[Groups(['api'])]
    public ?int $id = null;

    #[Groups(['api'])]
    public ?string $createdAt = null;

    #[Groups(['api'])]
    public ?string $updatedAt = null;

    #[Groups(['api'])]
    public ?string $activeAt = null;

    #[Groups(['api'])]
    public ?string $status = null;

    #[Groups(['api'])]
    public array $properties = [];

    public function __construct()
    {
        // Constructor can be empty or accept parameters if needed
    }
}
