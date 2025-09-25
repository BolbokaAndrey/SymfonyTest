<?php

namespace App\Controller;

use App\Dto\NewsDto;
use App\Entity\News;
use App\Repository\NewsRepository;
use App\Service\NewsApiService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use OpenApi\Attributes as OA;

#[Route('/api/news', name: 'api_news')]
#[OA\Tag(name: 'News')]
final class NewsApiController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Список новостей',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: NewsDto::class))
        )
    )]
    public function index(NewsRepository $newsRepository, NewsApiService $newsApiService, CacheInterface $cache): Response
    {
        $data = $cache->get('api_news', function (ItemInterface $item) use ($newsRepository, $newsApiService){
            $item->expiresAfter(60);

            $news = $newsRepository->findBy([], ['createdAt' => 'DESC']);
            $data = [];
            foreach ($news as $newsItem) {
                $data['items'][] = $newsApiService->serializeNews($newsItem);
            }
            return $data;
        });


        return new JsonResponse($data, 200);
    }

    #[Route('/{id}', name: '_get', methods: ['GET'])]
    #[OA\Parameter(
        name: 'id',
        description: 'ID новости',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Новость',
        content: new Model(type: NewsDto::class)
    )]
    public function get(int $id, NewsRepository $newsRepository, NewsApiService $newsApiService): Response
    {
        $news = $newsRepository->findOneBy(['id' => $id], ['createdAt' => 'DESC']);

        if (!$news) {
            return new JsonResponse(['error' => 'News not found'], 404);
        }

        return new JsonResponse($newsApiService->serializeNews($news), 200);
    }

    #[Route('', name: '_create', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\MediaType(
        mediaType: 'application/json',
        schema: new OA\Schema(ref: new Model(type: NewsDto::class)))
    )]
    #[OA\Response(
        response: 201,
        description: 'ОК',
    )]
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
    #[OA\Parameter(
        name: 'id',
        description: 'ID новости',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(content: new OA\MediaType(
        mediaType: 'application/json',
        schema: new OA\Schema(ref: new Model(type: NewsDto::class)))
    )]
    #[OA\Response(
        response: 201,
        description: 'ОК',
    )]
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
    #[OA\Parameter(
        name: 'id',
        description: 'ID новости',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 201,
        description: 'ОК',
    )]
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
