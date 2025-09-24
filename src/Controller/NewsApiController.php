<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/news', name: 'api_news')]
final class NewsApiController extends AbstractController
{
    #[Route('', name: '')]
    public function index(NewsRepository $newsRepository): Response
    {
        $news = $newsRepository->findBy([], ['createdAt' => 'DESC']);
        dd($news);
        return new JsonResponse();
    }
}
