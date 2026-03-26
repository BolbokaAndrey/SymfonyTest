<?php

namespace App\Controller;

use App\Entity\PropertyDefinition;
use App\Repository\PropertyDefinitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    #[IsGranted('ROLE_CONTENT_MANAGER')]
    public function index(): Response
    {
        $properties = $this->propertyDefinitionRepository->findAll();

        return $this->render('admin_content_properties/index.html.twig', [
            'title' => 'Свойства',
            'properties' => $properties
        ]);
    }

    #[Route('/new', name: '_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function newProperty(Request $request): Response
    {
        $propertyDefinition = new PropertyDefinition();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('property_form', $request->request->get('_csrf_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token');
            }

            $this->handleFormSubmission($propertyDefinition, $request);

            $existing = $this->propertyDefinitionRepository->findOneBy(['code' => $propertyDefinition->getCode()]);
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
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request): Response
    {
        $propertyDefinition = $this->propertyDefinitionRepository->find($id);
        if (!$propertyDefinition) {
            throw $this->createNotFoundException('Свойство не найдено');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('property_form', $request->request->get('_csrf_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token');
            }

            $this->handleFormSubmission($propertyDefinition, $request);

            $existing = $this->propertyDefinitionRepository->findOneBy(['code' => $propertyDefinition->getCode()]);
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

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteProperty(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

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
