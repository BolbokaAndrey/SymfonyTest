<?php

namespace App\Controller;

use App\Entity\NewsItem;
use App\Entity\PropertyValue;
use App\Repository\NewsItemRepository;
use App\Repository\PropertyDefinitionRepository;
use App\Service\NewsItemApiService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class NewsItemApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NewsItemRepository $newsItemRepository,
        private readonly PropertyDefinitionRepository $propertyDefinitionRepository,
        private readonly NewsItemApiService $newsItemApiService,
        private readonly NotificationService $notificationService,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/news-items', name: 'api_news_items_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONTENT_MANAGER');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(10, $request->query->getInt('limit', 20)));
        $status = $request->query->get('status');
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->newsItemRepository->createQueryBuilder('ni')
            ->leftJoin('ni.propertyValues', 'pv')
            ->leftJoin('pv.propertyDefinition', 'pd')
            ->orderBy('ni.createdAt', 'DESC');

        if ($status) {
            $queryBuilder->andWhere('ni.status = :status')
                ->setParameter('status', $status);
        }

        $queryBuilder->setFirstResult($offset)
            ->setMaxResults($limit);

        $newsItems = $queryBuilder->getQuery()->getResult();

        $total = $this->newsItemRepository->createQueryBuilder('ni')
            ->select('COUNT(ni.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $data = [
            'items' => $this->newsItemApiService->serializeNewsItems($newsItems),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => ceil($total / $limit),
            ],
            'propertyDefinitions' => $this->newsItemApiService->serializePropertyDefinitions(
                $this->propertyDefinitionRepository->findAllActiveOrdered()
            ),
        ];

        return new JsonResponse($data);
    }

    #[Route('/news-items/{id}', name: 'api_news_items_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONTENT_MANAGER');

        $newsItem = $this->newsItemRepository->find($id);
        if (!$newsItem) {
            return new JsonResponse(['error' => 'Элемент не найден'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'item' => $this->newsItemApiService->serializeNewsItem($newsItem),
            'propertyDefinitions' => $this->newsItemApiService->serializePropertyDefinitions(
                $this->propertyDefinitionRepository->findAllActiveOrdered()
            ),
        ]);
    }

    #[Route('/news-items', name: 'api_news_items_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONTENT_MANAGER');

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Неверный JSON'], Response::HTTP_BAD_REQUEST);
        }

        $newsItem = new NewsItem();
        $newsItem->setStatus($data['status'] ?? 'draft');

        if (isset($data['activeAt'])) {
            $newsItem->setActiveAt(new \DateTimeImmutable($data['activeAt']));
        }

        $errors = $this->validateAndSetProperties($newsItem, $data['properties'] ?? []);
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($newsItem);
        $this->entityManager->flush();

        // TODO: Send email notification to admin
        $this->notificationService->notifyAdminAboutNewItem($newsItem, $this->getUser());

        return new JsonResponse([
            'item' => $this->newsItemApiService->serializeNewsItem($newsItem),
            'message' => 'Элемент успешно создан',
        ], Response::HTTP_CREATED);
    }

    #[Route('/news-items/{id}', name: 'api_news_items_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONTENT_MANAGER');

        $newsItem = $this->newsItemRepository->find($id);
        if (!$newsItem) {
            return new JsonResponse(['error' => 'Элемент не найден'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Неверный JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['status'])) {
            $newsItem->setStatus($data['status']);
        }

        if (isset($data['activeAt'])) {
            $newsItem->setActiveAt(
                $data['activeAt'] ? new \DateTimeImmutable($data['activeAt']) : null
            );
        }

        // Remove existing properties
        foreach ($newsItem->getPropertyValues() as $propertyValue) {
            $this->entityManager->remove($propertyValue);
        }

        $errors = $this->validateAndSetProperties($newsItem, $data['properties'] ?? []);
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        // Send email notification to admin
        $this->notificationService->notifyAdminAboutUpdatedItem($newsItem, $this->getUser());

        return new JsonResponse([
            'item' => $this->newsItemApiService->serializeNewsItem($newsItem),
            'message' => 'Элемент успешно обновлен',
        ]);
    }

    #[Route('/news-items/{id}', name: 'api_news_items_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONTENT_MANAGER');

        $newsItem = $this->newsItemRepository->find($id);
        if (!$newsItem) {
            return new JsonResponse(['error' => 'Элемент не найден'], Response::HTTP_NOT_FOUND);
        }

        $itemTitle = $newsItem->getPropertiesAsArray()['title'] ?? 'Без названия';

        // Send email notification to admin before deleting
        $this->notificationService->notifyAdminAboutDeletedItem(
            $newsItem->getId(),
            $itemTitle,
            $this->getUser()
        );

        $this->entityManager->remove($newsItem);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Элемент успешно удален']);
    }

    private function validateAndSetProperties(NewsItem $newsItem, array $properties): array
    {
        $errors = [];
        $propertyDefinitions = $this->propertyDefinitionRepository->findAllActiveOrdered();
        $propertyDefinitionsMap = [];

        foreach ($propertyDefinitions as $definition) {
            $propertyDefinitionsMap[$definition->getName()] = $definition;
        }

        foreach ($properties as $propertyName => $propertyData) {
            if (!isset($propertyDefinitionsMap[$propertyName])) {
                $errors[] = "Свойство '$propertyName' не определено";
                continue;
            }

            $definition = $propertyDefinitionsMap[$propertyName];

            // Check required
            if ($definition->isRequired() && (empty($propertyData) || $propertyData === '')) {
                $errors[] = "Свойство '$propertyName' обязательно для заполнения";
                continue;
            }

            // Skip empty values for non-required fields
            if (!$definition->isRequired() && (empty($propertyData) || $propertyData === '')) {
                continue;
            }

            // Create property value
            $propertyValue = new PropertyValue();
            $propertyValue->setPropertyDefinition($definition);
            $propertyValue->setNewsItem($newsItem);

            if ($definition->isMultiple()) {
                $values = is_array($propertyData) ? $propertyData : [$propertyData];
                $propertyValue->setValue(array_filter($values, fn($v) => !empty($v)));
            } else {
                $propertyValue->setSingleValue($propertyData);
            }

            $newsItem->addPropertyValue($propertyValue);
        }

        return $errors;
    }
}
