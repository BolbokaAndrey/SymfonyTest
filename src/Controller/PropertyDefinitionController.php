<?php

namespace App\Controller;

use App\Entity\PropertyDefinition;
use App\Repository\PropertyDefinitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/properties')]
final class PropertyDefinitionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PropertyDefinitionRepository $propertyDefinitionRepository,
    ) {}

    #[Route('', name: 'admin_properties_list')]
    public function list(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $propertyDefinitions = $this->propertyDefinitionRepository->findBy([], ['sortOrder' => 'ASC']);

        return $this->render('admin/properties/list.html.twig', [
            'title' => 'Управление свойствами',
            'propertyDefinitions' => $propertyDefinitions,
        ]);
    }

    #[Route('/new', name: 'admin_properties_new')]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $propertyDefinition = new PropertyDefinition();

        if ($request->isMethod('POST')) {
            $this->handleFormSubmission($propertyDefinition, $request);

            // Check for duplicate name
            $existing = $this->propertyDefinitionRepository->findOneBy(['name' => $propertyDefinition->getName()]);
            if ($existing) {
                $this->addFlash('error', 'Свойство с таким именем уже существует');
                return $this->redirectToRoute('admin_properties_new');
            }

            $this->entityManager->persist($propertyDefinition);
            $this->entityManager->flush();

            $this->addFlash('success', 'Свойство успешно создано');
            return $this->redirectToRoute('admin_properties_list');
        }

        return $this->render('admin/properties/form.html.twig', [
            'title' => 'Новое свойство',
            'propertyDefinition' => $propertyDefinition,
            'mode' => 'create',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_properties_edit')]
    public function edit(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $propertyDefinition = $this->propertyDefinitionRepository->find($id);
        if (!$propertyDefinition) {
            throw $this->createNotFoundException('Свойство не найдено');
        }

        if ($request->isMethod('POST')) {
            $this->handleFormSubmission($propertyDefinition, $request);

            // Check for duplicate name (excluding current)
            $existing = $this->propertyDefinitionRepository->findOneBy(['name' => $propertyDefinition->getName()]);
            if ($existing && $existing->getId() !== $id) {
                $this->addFlash('error', 'Свойство с таким именем уже существует');
                return $this->redirectToRoute('admin_properties_edit', ['id' => $id]);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Свойство успешно обновлено');
            return $this->redirectToRoute('admin_properties_list');
        }

        return $this->render('admin/properties/form.html.twig', [
            'title' => 'Редактирование свойства',
            'propertyDefinition' => $propertyDefinition,
            'mode' => 'edit',
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_properties_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $propertyDefinition = $this->propertyDefinitionRepository->find($id);
        if (!$propertyDefinition) {
            throw $this->createNotFoundException('Свойство не найдено');
        }

        // Check if there are any property values using this definition
        if ($propertyDefinition->getPropertyValues()->count() > 0) {
            $this->addFlash('error', 'Невозможно удалить: свойство используется в элементах контента');
            return $this->redirectToRoute('admin_properties_list');
        }

        $this->entityManager->remove($propertyDefinition);
        $this->entityManager->flush();

        $this->addFlash('success', 'Свойство успешно удалено');
        return $this->redirectToRoute('admin_properties_list');
    }

    #[Route('/reorder', name: 'admin_properties_reorder', methods: ['POST'])]
    public function reorder(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = $request->request->all('order');
        if (!$order) {
            $this->addFlash('error', 'Неверный формат данных');
            return $this->redirectToRoute('admin_properties_list');
        }

        foreach ($order as $id => $sortOrder) {
            $propertyDefinition = $this->propertyDefinitionRepository->find($id);
            if ($propertyDefinition) {
                $propertyDefinition->setSortOrder((int) $sortOrder);
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Порядок свойств обновлен');
        return $this->redirectToRoute('admin_properties_list');
    }

    #[Route('/{id}/toggle', name: 'admin_properties_toggle', methods: ['POST'])]
    public function toggle(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $propertyDefinition = $this->propertyDefinitionRepository->find($id);
        if (!$propertyDefinition) {
            throw $this->createNotFoundException('Свойство не найдено');
        }

        $propertyDefinition->setActive(!$propertyDefinition->isActive());
        $this->entityManager->flush();

        $message = $propertyDefinition->isActive() ? 'Свойство активировано' : 'Свойство деактивировано';
        $this->addFlash('success', $message);

        return $this->redirectToRoute('admin_properties_list');
    }

    private function handleFormSubmission(PropertyDefinition $propertyDefinition, Request $request): void
    {
        $propertyDefinition->setName(trim($request->request->get('name')));
        $propertyDefinition->setType($request->request->get('type'));
        $propertyDefinition->setRequired($request->request->getBoolean('required'));
        $propertyDefinition->setMultiple($request->request->getBoolean('multiple'));
        $propertyDefinition->setSortOrder((int) $request->request->get('sortOrder', 0));
        $propertyDefinition->setDescription(trim($request->request->get('description')));

        // Handle validation rules
        $validationRules = [];
        if ($request->request->get('validation_min_length')) {
            $validationRules['minLength'] = (int) $request->request->get('validation_min_length');
        }
        if ($request->request->get('validation_max_length')) {
            $validationRules['maxLength'] = (int) $request->request->get('validation_max_length');
        }
        if ($request->request->get('validation_pattern')) {
            $validationRules['pattern'] = $request->request->get('validation_pattern');
        }

        $propertyDefinition->setValidationRules(!empty($validationRules) ? $validationRules : null);

        // Handle default value
        $defaultValue = $request->request->get('default_value');
        if (!empty($defaultValue)) {
            $propertyDefinition->setDefaultValue(['value' => $defaultValue]);
        } else {
            $propertyDefinition->setDefaultValue(null);
        }
    }
}
