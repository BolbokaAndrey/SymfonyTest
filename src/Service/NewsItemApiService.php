<?php

namespace App\Service;

use App\Dto\NewsItemDto;
use App\Dto\PropertyDefinitionDto;
use App\Entity\NewsItem;
use App\Entity\PropertyDefinition;
use Symfony\Component\Serializer\SerializerInterface;

class NewsItemApiService
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {}

    public function serializeNewsItem(NewsItem $newsItem): array
    {
        $dto = new NewsItemDto();
        $dto->id = $newsItem->getId();
        $dto->createdAt = $newsItem->getCreatedAt()->format('c');
        $dto->properties = $newsItem->getPropertiesAsArray();

        return $this->serializer->normalize($dto, null, ['groups' => ['api']]);
    }

    public function serializeNewsItems(array $newsItems): array
    {
        return array_map([$this, 'serializeNewsItem'], $newsItems);
    }

    public function serializePropertyDefinition(PropertyDefinition $definition): array
    {
        $dto = new PropertyDefinitionDto();
        $dto->id = $definition->getId();
        $dto->name = $definition->getName();
        $dto->type = $definition->getType();
        $dto->required = $definition->isRequired();
        $dto->multiple = $definition->isMultiple();
        $dto->sortOrder = $definition->getSortOrder();
        $dto->defaultValue = $definition->getDefaultValue();
        $dto->description = $definition->getDescription();
        $dto->active = $definition->isActive();

        return $this->serializer->normalize($dto, null, ['groups' => ['api']]);
    }

    public function serializePropertyDefinitions(array $definitions): array
    {
        return array_map([$this, 'serializePropertyDefinition'], $definitions);
    }
}
