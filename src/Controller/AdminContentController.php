<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\NewsItem;
use App\Entity\PropertyDefinition;
use App\Entity\PropertyValue;
use App\Repository\NewsItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminContentController extends AbstractController
{
    #[Route('/admin/content', name: 'admin_content')]
    public function index(): Response
    {
        return $this->render('admin_content/index.html.twig', [
            'title' => 'Контент',
        ]);
    }

    #[Route('/admin/content/news', name: 'admin_content_news')]
    public function news(NewsItemRepository $newsRepository): Response
    {
        $news = $newsRepository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin_content/news.html.twig', [
            'title' => 'Новости',
            'news' => $news
        ]);
    }

    #[Route('/admin/content/news/new', name: 'admin_content_news_new')]
    public function newNews(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CONTENT_MANAGER');

        $error = null;

        $contentProperty = $em->getRepository(PropertyDefinition::class)->findOneBy(['name' => 'content']);
        $imageProperty = $em->getRepository(PropertyDefinition::class)->findOneBy(['name' => 'image']);
        $tagsProperty = $em->getRepository(PropertyDefinition::class)->findOneBy(['name' => 'tags']);

        if ($request->isMethod('POST')) {
            try {
                // Validation
                $title = trim($request->request->get('title'));
                $text = trim($request->request->get('text'));
                $activeDate = $request->request->get('active');
                $tags = $request->request->all('tags');
                $tags = array_values(array_unique(array_filter($tags)));

                if (empty($title)) {
                    $error = 'Название новости обязательно для заполнения';
                } elseif (empty($activeDate)) {
                    $error = 'Дата активации обязательна для заполнения';
                } else {
                    $news = new NewsItem();

                    $news->setName($title);
                    $news->setActiveAt(new \DateTimeImmutable($activeDate));

                    $pictureFile = $request->files->get('picture');
                    if ($pictureFile) {
                        if (!$pictureFile->isValid()) {
                            $error = 'Ошибка загрузки файла';
                        } else {
                            $file = new File();
                            $fileName = uniqid() . '.' . $pictureFile->guessExtension();
                            $file->setPath('uploads/news/' . $fileName);

                            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/news';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }

                            $pictureFile->move($uploadDir, $fileName);

                            $em->persist($file);

                            if ($imageProperty) {
                                $imageValue = new PropertyValue();
                                $imageValue->setPropertyDefinition($imageProperty);
                                $imageValue->setSingleValue($file->getPath());
                                $imageValue->setNewsItem($news);
                                $news->addPropertyValue($imageValue);
                                $em->persist($imageValue);
                            }
                        }
                    }



                    if ($contentProperty) {
                        $textValue = new PropertyValue();
                        $textValue->setPropertyDefinition($contentProperty);
                        $textValue->setSingleValue($text);
                        $textValue->setNewsItem($news);
                        $news->addPropertyValue($textValue);
                        $em->persist($textValue);
                    }

                    if ($tagsProperty) {
                        $tagsValue = new PropertyValue();
                        $tagsValue->setPropertyDefinition($tagsProperty);
                        $tagsValue->setValue($tags);
                        $tagsValue->setNewsItem($news);
                        $news->addPropertyValue($tagsValue);
                        $em->persist($tagsValue);
                    }

                    $em->persist($news);
                    $em->flush();

                    $this->addFlash('success', 'Новость успешно создана');
                    return $this->redirectToRoute('admin_content_news');
                }
            } catch (\Exception $e) {
                $error = 'Произошла ошибка при создании новости: ' . $e->getMessage();
            }
        }

        return $this->render('admin_content/news_form.html.twig', [
            'title' => 'Новая новость',
            'mode' => 'create',
            'error' => $error,
            'news' => [
                'title' => $title ?? '',
                'text' => $contentProperty,
                'image' => $imageProperty,
                'activeAt' => $activeDate ?? '',
                'tags' => $tagsProperty,
            ],
        ]);
    }

}
