<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

readonly class NewsDto
{
    public function __construct(
        #[OA\Property(description: 'ID новости', example: 1)]
        public ?int    $id = null,
        #[OA\Property(description: 'Название новости', example: 'Новость')]
        public ?string $title = null,
        #[OA\Property(description: 'Текст новости', example: 'Текст новости')]
        public ?string $text = null,
        #[OA\Property(description: 'Дата создания новости', example: '2022-01-01 00:00:00')]
        public ?string $createdAt = null,
        #[OA\Property(description: 'Источник новости', example: 'Источник новости')]
        public ?string $source = null,
        #[OA\Property(description: 'Изображение новости', example: 'Изображение новости')]
        public ?string $image = null,
        #[OA\Property(description: 'Теги новости', example: 'Теги новости')]
        public ?array  $tags = null,
    )
    {
    }
}
