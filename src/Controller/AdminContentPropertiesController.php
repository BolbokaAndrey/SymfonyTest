<?php

namespace App\Controller;

use App\Repository\PropertyDefinitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
