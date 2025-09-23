<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use App\Repository\PropertyDefinitionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/content', name: 'admin_content')]
final class AdminContentController extends AbstractController
{
    #[Route('', name: '')]
    public function index(): Response
    {
        return $this->render('admin_content/index.html.twig', [
            'title' => 'Контент'
        ]);
    }

    #[Route('/news', name: '_news')]
    public function news(NewsRepository $newsRepository): Response
    {
        $news = $newsRepository->findAll();

        $formattedNews = [];
        foreach ($news as $newsItem) {
            $properties = [];
            foreach ($newsItem->getProperties() as $propertyValue) {
                if ($propertyValue->getPropertyDefinition()) {
                    $code = $propertyValue->getPropertyDefinition()->getCode();
                    $properties[$code] = [
                        'id' => $propertyValue->getId(),
                        'name' => $propertyValue->getPropertyDefinition()->getName(),
                        'value' => $propertyValue->getValue() ?? '',
                        'code' => $propertyValue->getPropertyDefinition()->getCode(),
                        'type' => $propertyValue->getPropertyDefinition()->getType()
                    ];
                }
            }

            $formattedNews[] = [
                'id' => $newsItem->getId(),
                'title' => $newsItem->getTitle(),
                'text' => $newsItem->getText(),
                'createdAt' => $newsItem->getCreatedAt(),
                'properties' => $properties
            ];
        }

        return $this->render('admin_content/news.html.twig', [
            'title' => 'Новости',
            'news' => $formattedNews
        ]);
    }

    #[Route('/properties', name: '_properties')]
    public function properties(PropertyDefinitionRepository $propertyDefinitionRepository): Response
    {
        $properties = $propertyDefinitionRepository->findAll();

        return $this->render('admin_content/properties.html.twig', [
            'title' => 'Свойства',
            'properties' => $properties
        ]);
    }
}
