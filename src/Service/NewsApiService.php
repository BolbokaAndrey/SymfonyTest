<?php

namespace App\Service;

use App\Dto\NewsDto;
use App\Entity\News;
use App\Entity\PropertyDefinition;
use App\Entity\PropertyValue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class NewsApiService
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager,
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

    public function createNews(Request $request): void
    {
        $news = new News();
        $news->setTitle($request->request->get('title'));
        $news->setText($request->request->get('text'));
        $news->setCreatedAt(new \DateTimeImmutable($request->request->get('createdAt')));
        $errors = $this->validator->validate($news);
        if (count($errors) > 0) {
            throw new InvalidArgumentException('Ошибка валидации', 400);
        }

        $property = new PropertyValue();
        $property->setValue($request->request->get('source'));
        $property->setPropertyDefinition($this->entityManager->getRepository(PropertyDefinition::class)->findOneBy(['code' => 'source']));
        $news->addProperty($property);

        $property = new PropertyValue();
        $property->setValue($request->request->get('image'));
        $property->setPropertyDefinition($this->entityManager->getRepository(PropertyDefinition::class)->findOneBy(['code' => 'image']));
        $news->addProperty($property);

        $property = new PropertyValue();
        $property->setValue(implode(',', $request->request->all('tags')));
        $property->setPropertyDefinition($this->entityManager->getRepository(PropertyDefinition::class)->findOneBy(['code' => 'tags']));
        $news->addProperty($property);

        $this->entityManager->persist($news);
        $this->entityManager->flush();
    }

    public function updateNews(int $id, Request $request): void
    {
        $news = $this->entityManager->getRepository(News::class)->find($id);
        if (!$news) {
            throw new InvalidArgumentException('Новость не найдена', 404);
        }

        $news->setTitle($request->request->get('title'));
        $news->setText($request->request->get('text'));
        $news->setCreatedAt(new \DateTimeImmutable($request->request->get('createdAt')));
        $errors = $this->validator->validate($news);
        if (count($errors) > 0) {
            throw new InvalidArgumentException('Ошибка валидации', 400);
        }

        $news->getProperties()->findFirst(function ($key, $property) {
            return $property->getPropertyDefinition()->getCode() === 'source';
        })?->setValue($request->request->get('source'));

        $news->getProperties()->findFirst(function ($key, $property) {
            return $property->getPropertyDefinition()->getCode() === 'image';
        })?->setValue($request->request->get('image'));

        $news->getProperties()->findFirst(function ($key, $property) {
            return $property->getPropertyDefinition()->getCode() === 'tags';
        })?->setValue(implode(',', $request->request->all('tags')));

        $this->entityManager->persist($news);
        $this->entityManager->flush();
    }

    public function deleteNews(int $id): void
    {
        $news = $this->entityManager->getRepository(News::class)->find($id);
        if (!$news) {
            throw new InvalidArgumentException('Новость не найдена', 404);
        }

        $this->entityManager->remove($news);
        $this->entityManager->flush();
    }
}
