<?php

namespace App\Controller;

use App\Entity\PropertyDefinition;
use App\Repository\PropertyDefinitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/properties', name: 'admin_properties')]
final class AdminContentPropertiesController extends AbstractController
{
    public function __construct(
        private readonly PropertyDefinitionRepository $propertyDefinitionRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    #[Route('', name: '')]
    public function index(): Response
    {
        $properties = $this->propertyDefinitionRepository->findAll();

        return $this->render('admin_content_properties/index.html.twig', [
            'title' => 'Свойства',
            'properties' => $properties
        ]);
    }

    #[Route('/new', name: '_new')]
    public function newProperty(Request $request): Response
    {
        $propertyDefinition = new PropertyDefinition();

        if ($request->isMethod('POST')) {
            $this->handleFormSubmission($propertyDefinition, $request);

            $existing = $this->propertyDefinitionRepository->findOneBy(['name' => $propertyDefinition->getCode()]);
            if ($existing) {
                $this->addFlash('error', 'Свойство с таким кодом уже существует');
                return $this->redirectToRoute('admin_properties_new');
            }

            $this->entityManager->persist($propertyDefinition);
            $this->entityManager->flush();

            $this->addFlash('success', 'Свойство успешно создано');
            return $this->redirectToRoute('admin_properties');
        }

        return $this->render('admin_content_properties/form.html.twig', [
            'title' => 'Создание свойства',
            'propertyDefinition' => $propertyDefinition
        ]);
    }

    #[Route('/{id}/edit', name: '_edit')]
    public function edit(int $id, Request $request): Response
    {
        $propertyDefinition = $this->propertyDefinitionRepository->find($id);
        if (!$propertyDefinition) {
            throw $this->createNotFoundException('Свойство не найдено');
        }

        if ($request->isMethod('POST')) {
            $this->handleFormSubmission($propertyDefinition, $request);

            $existing = $this->propertyDefinitionRepository->findOneBy(['name' => $propertyDefinition->getCode()]);
            if ($existing && $existing->getId() !== $id) {
                $this->addFlash('error', 'Свойство с таким кодом уже существует');
                return $this->redirectToRoute('admin_properties_edit', ['id' => $id]);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Свойство успешно обновлено');
            return $this->redirectToRoute('admin_properties');
        }

        return $this->render('admin_content_properties/form.html.twig', [
            'title' => 'Редактирование свойства',
            'propertyDefinition' => $propertyDefinition,
        ]);
    }

    #[Route('/{id}/delete', name: '_delete')]
    public function deleteProperty(int $id): Response
    {
        $propertyDefinition = $this->propertyDefinitionRepository->find($id);

        if (!$propertyDefinition) {
            throw $this->createNotFoundException('Свойство не найдено');
        }

        $this->entityManager->remove($propertyDefinition);
        $this->entityManager->flush();

        $this->addFlash('success', 'Свойство успешно удалено');
        return $this->redirectToRoute('admin_properties');
    }

    private function handleFormSubmission(PropertyDefinition $propertyDefinition, Request $request): void
    {
        $propertyDefinition->setName(trim($request->request->get('name')));
        $propertyDefinition->setCode(trim($request->request->get('code')));
        $propertyDefinition->setType($request->request->get('type'));
        $propertyDefinition->setRequired($request->request->getBoolean('required'));
        $propertyDefinition->setMultiple($request->request->getBoolean('multiple'));
        $propertyDefinition->setDescription(trim($request->request->get('description')));
    }
}
