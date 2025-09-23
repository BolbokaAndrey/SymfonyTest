<?php

namespace App\Controller;

use App\Entity\News;
use App\Entity\PropertyDefinition;
use App\Entity\PropertyValue;
use App\Repository\NewsRepository;
use App\Repository\PropertyDefinitionRepository;
use App\Repository\PropertyValueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/content', name: 'admin_content')]
final class AdminContentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NewsRepository $newsRepository,
        private readonly PropertyValueRepository $propertyValueRepository,
        private readonly PropertyDefinitionRepository $propertyDefinitionRepository,
    )
    {
    }

    #[Route('', name: '')]
    public function index(): Response
    {
        return $this->render('admin_content/index.html.twig', [
            'title' => 'Контент'
        ]);
    }

    #[Route('/news', name: '_news')]
    public function news(): Response
    {
        $news = $this->newsRepository->findAll();

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

    #[Route('/news/new', name: '_news_new')]
    public function new(Request $request): Response
    {
        $news = new News();
        $property = new PropertyValue();
        if ($request->isMethod('POST')) {

            $news->setTitle($request->request->get('title'));
            $news->setText($request->request->get('text'));
            $news->setCreatedAt(new \DateTimeImmutable($request->request->get('createdAt')));
            $property->setValue($request->request->get('source'));
            $property->setPropertyDefinition($this->propertyDefinitionRepository->findOneBy(['code' => 'source']));
            $news->addProperty($property);

            $this->entityManager->persist($property);
            $this->entityManager->persist($news);
            $this->entityManager->flush();

            $this->addFlash('success', 'Новость успешно создана');
            return $this->redirectToRoute('admin_content_news');
        }

        return $this->render('admin_content/news_form.html.twig', [
            'title' => 'Создание новости',
            'propertyDefinition' => $news
        ]);
    }

    #[Route('/news/{id}/edit', name: '_news_edit')]
    public function edit(int $id, Request $request): Response
    {
        $news = $this->newsRepository->find($id);
        $properties = $news->getProperties();
        if (!$news) {
            throw $this->createNotFoundException('Новость не найдена');
        }

        if ($request->isMethod('POST')) {
            $news->setTitle($request->request->get('title'));
            $news->setText($request->request->get('text'));
            $news->setCreatedAt(new \DateTimeImmutable($request->request->get('createdAt')));
            foreach ($properties as $property) {
                $propertyCode = $property->getPropertyDefinition()->getCode();
                match ($propertyCode) {
                    'source' => $property->setValue($request->request->get('source')),
                };
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Новость успешно обновлена');
            return $this->redirectToRoute('admin_content_news');
        }

        return $this->render('admin_content/news_form.html.twig', [
            'title' => 'Редактирование новости',
            'news' => $news,
        ]);
    }
}
