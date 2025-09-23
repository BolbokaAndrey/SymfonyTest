<?php

namespace App\Controller;

use App\Entity\PropertyDefinition;
use App\Repository\PropertyDefinitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class PropertyDefinitionApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PropertyDefinitionRepository $propertyDefinitionRepository,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/property-definitions', name: 'api_admin_property_definitions_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $definitions = $this->propertyDefinitionRepository->findAll();

        $data = array_map(function(PropertyDefinition $definition) {
            return [
                'id' => $definition->getId(),
                'name' => $definition->getName(),
                'type' => $definition->getType(),
                'required' => $definition->isRequired(),
                'multiple' => $definition->isMultiple(),
                'sortOrder' => $definition->getSortOrder(),
                'defaultValue' => $definition->getDefaultValue(),
                'description' => $definition->getDescription(),
                'active' => $definition->isActive(),
            ];
        }, $definitions);

        return new JsonResponse($data);
    }

    #[Route('/property-definitions', name: 'api_admin_property_definitions_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Неверный JSON'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validatePropertyDefinitionData($data);
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $definition = new PropertyDefinition();
        $this->setPropertyDefinitionData($definition, $data);

        // Check for duplicate name
        $existing = $this->propertyDefinitionRepository->findOneBy(['name' => $data['name']]);
        if ($existing) {
            return new JsonResponse(['error' => 'Свойство с таким именем уже существует'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($definition);
        $this->entityManager->flush();

        return new JsonResponse([
            'definition' => $this->serializePropertyDefinition($definition),
            'message' => 'Определение свойства успешно создано',
        ], Response::HTTP_CREATED);
    }

    #[Route('/property-definitions/{id}', name: 'api_admin_property_definitions_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $definition = $this->propertyDefinitionRepository->find($id);
        if (!$definition) {
            return new JsonResponse(['error' => 'Определение свойства не найдено'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Неверный JSON'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validatePropertyDefinitionData($data);
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Check for duplicate name (excluding current)
        $existing = $this->propertyDefinitionRepository->findOneBy(['name' => $data['name']]);
        if ($existing && $existing->getId() !== $id) {
            return new JsonResponse(['error' => 'Свойство с таким именем уже существует'], Response::HTTP_BAD_REQUEST);
        }

        $this->setPropertyDefinitionData($definition, $data);
        $this->entityManager->flush();

        return new JsonResponse([
            'definition' => $this->serializePropertyDefinition($definition),
            'message' => 'Определение свойства успешно обновлено',
        ]);
    }

    #[Route('/property-definitions/{id}', name: 'api_admin_property_definitions_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $definition = $this->propertyDefinitionRepository->find($id);
        if (!$definition) {
            return new JsonResponse(['error' => 'Определение свойства не найдено'], Response::HTTP_NOT_FOUND);
        }

        // Check if there are any property values using this definition
        if ($definition->getPropertyValues()->count() > 0) {
            return new JsonResponse(['error' => 'Невозможно удалить: определение используется в элементах'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($definition);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Определение свойства успешно удалено']);
    }

    #[Route('/property-definitions/{id}/toggle', name: 'api_admin_property_definitions_toggle', methods: ['PATCH'])]
    public function toggle(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $definition = $this->propertyDefinitionRepository->find($id);
        if (!$definition) {
            return new JsonResponse(['error' => 'Определение свойства не найдено'], Response::HTTP_NOT_FOUND);
        }

        $definition->setActive(!$definition->isActive());
        $this->entityManager->flush();

        return new JsonResponse([
            'definition' => $this->serializePropertyDefinition($definition),
            'message' => $definition->isActive() ? 'Свойство активировано' : 'Свойство деактивировано',
        ]);
    }

    #[Route('/property-definitions/reorder', name: 'api_admin_property_definitions_reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['order'])) {
            return new JsonResponse(['error' => 'Неверный формат данных'], Response::HTTP_BAD_REQUEST);
        }

        $order = $data['order'];
        foreach ($order as $id => $sortOrder) {
            $definition = $this->propertyDefinitionRepository->find($id);
            if ($definition) {
                $definition->setSortOrder($sortOrder);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Порядок свойств обновлен']);
    }

    private function validatePropertyDefinitionData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Название обязательно';
        }

        if (empty($data['type'])) {
            $errors[] = 'Тип обязателен';
        } elseif (!in_array($data['type'], ['text', 'textarea', 'number', 'date', 'file', 'select', 'checkbox', 'radio'])) {
            $errors[] = 'Неподдерживаемый тип';
        }

        return $errors;
    }

    private function setPropertyDefinitionData(PropertyDefinition $definition, array $data): void
    {
        $definition->setName($data['name']);
        $definition->setType($data['type']);
        $definition->setRequired($data['required'] ?? false);
        $definition->setMultiple($data['multiple'] ?? false);
        $definition->setSortOrder($data['sortOrder'] ?? 0);
        $definition->setDefaultValue($data['defaultValue'] ?? null);
        $definition->setDescription($data['description'] ?? null);
        $definition->setActive($data['active'] ?? true);
    }

    private function serializePropertyDefinition(PropertyDefinition $definition): array
    {
        return [
            'id' => $definition->getId(),
            'name' => $definition->getName(),
            'type' => $definition->getType(),
            'required' => $definition->isRequired(),
            'multiple' => $definition->isMultiple(),
            'sortOrder' => $definition->getSortOrder(),
            'defaultValue' => $definition->getDefaultValue(),
            'description' => $definition->getDescription(),
            'active' => $definition->isActive(),
        ];
    }
}
