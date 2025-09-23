<?php

namespace App\Controller;

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
    public function news(): Response
    {
        return $this->render('admin_content/news.html.twig', [
            'title' => 'Новости'
        ]);
    }
}
