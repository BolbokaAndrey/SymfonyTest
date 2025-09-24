<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use App\Service\NewsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/news', name: 'api_news')]
final class NewsApiController extends AbstractController
{
    #[Route('', name: '')]
    public function index(NewsRepository $newsRepository, NewsApiService $newsApiService): Response
    {
        $news = $newsRepository->findBy([], ['createdAt' => 'DESC']);

        $data = [];
        foreach ($news as $newsItem) {
            $data['items'][] = $newsApiService->serializeNews($newsItem);
        }

        return new JsonResponse($data, 200);
    }

    #[Route('/{id}', name: '_get')]
    public function get(int $id, NewsRepository $newsRepository, NewsApiService $newsApiService): Response
    {
        $news = $newsRepository->findOneBy(['id' => $id], ['createdAt' => 'DESC']);

        if (!$news) {
            return new JsonResponse(['error' => 'News not found'], 404);
        }
        $data = $newsApiService->serializeNews($news);

        return new JsonResponse($data, 200);
    }
}
