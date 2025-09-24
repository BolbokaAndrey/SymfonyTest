<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use App\Service\NewsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

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

        return new JsonResponse($newsApiService->serializeNews($news), 200);
    }

    #[Route('', name: '_create', methods: ['POST'])]
    public function create(Request $request, NewsApiService $newsApiService): Response
    {
        try {
            $newsApiService->createNews($request);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        return new JsonResponse('ОК', 201);
    }

    #[Route('/{id}', name: '_update', methods: ['PUT'])]
    public function update(int $id, Request $request, NewsApiService $newsApiService): Response
    {
        try {
            $newsApiService->updateNews($id, $request);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        return new JsonResponse('ОК', 201);
    }

    #[Route('/{id}', name: '_delete', methods: ['DELETE'])]
    public function delete(int $id, NewsApiService $newsApiService): Response
    {
        try {
            $newsApiService->deleteNews($id);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        return new JsonResponse('ОК', 201);
    }
}
