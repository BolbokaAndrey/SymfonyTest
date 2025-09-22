<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class PropertyDefinitionDto
{
    #[Groups(['api'])]
    public ?int $id = null;

    #[Groups(['api'])]
    public ?string $name = null;

    #[Groups(['api'])]
    public ?string $type = null;

    #[Groups(['api'])]
    public ?bool $required = null;

    #[Groups(['api'])]
    public ?bool $multiple = null;

    #[Groups(['api'])]
    public ?int $sortOrder = null;

    #[Groups(['api'])]
    public ?array $validationRules = null;

    #[Groups(['api'])]
    public ?array $defaultValue = null;

    #[Groups(['api'])]
    public ?string $description = null;

    #[Groups(['api'])]
    public ?bool $active = null;
}
