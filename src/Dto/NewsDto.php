<?php

namespace App\Dto;

readonly class NewsDto
{
    public function __construct(
        public ?int    $id = null,
        public ?string $title = null,
        public ?string $text = null,
        public ?string $createdAt = null,
        public ?string $source = null,
        public ?string $image = null,
        public ?array  $tags = null,
    )
    {
    }
}
