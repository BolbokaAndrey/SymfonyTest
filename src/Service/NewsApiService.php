<?php

namespace App\Service;

use App\Dto\NewsDto;
use App\Entity\News;
use App\Repository\NewsRepository;
use Symfony\Component\Serializer\SerializerInterface;

readonly class NewsApiService
{
    public function __construct(
        private SerializerInterface $serializer,
    ){
    }


    public function serializeNews(News $news): array
    {
        $source = $news->getProperties()->findFirst(function ($key, $property) {
            return $property->getPropertyDefinition()->getCode() === 'source';
        })?->getValue();

        $image = $news->getProperties()->findFirst(function ($key, $property) {
            return $property->getPropertyDefinition()->getCode() === 'image';
        })?->getValue();

        $tags = $news->getProperties()->findFirst(function ($key, $property) {
            return $property->getPropertyDefinition()->getCode() === 'tags';
        })?->getValue();

        $dto = new NewsDto(
            $news->getId(),
            $news->getTitle(),
            $news->getText(),
            $news->getCreatedAt()->format('Y-m-d H:i:s'),
            $source,
            $image,
            explode(',', $tags),
        );

        return $this->serializer->normalize($dto);
    }
}
