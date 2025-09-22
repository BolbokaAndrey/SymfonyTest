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

        // Get property definitions for dynamic form
        $titleProperty = $em->getRepository(PropertyDefinition::class)->findOneBy(['name' => 'title']);
        $contentProperty = $em->getRepository(PropertyDefinition::class)->findOneBy(['name' => 'content']);
        $imageProperty = $em->getRepository(PropertyDefinition::class)->findOneBy(['name' => 'image']);

        if ($request->isMethod('POST')) {
            try {
                // Validation
                $title = trim($request->request->get('title'));
                $text = trim($request->request->get('text'));
                $activeDate = $request->request->get('active');

                if (empty($title)) {
                    $error = $titleProperty ? $titleProperty->getName() . ' обязательно для заполнения' : 'Название новости обязательно для заполнения';
                } elseif (empty($text)) {
                    $error = $contentProperty ? $contentProperty->getName() . ' обязательно для заполнения' : 'Текст новости обязателен для заполнения';
                } elseif (empty($activeDate)) {
                    $error = 'Дата активации обязательна для заполнения';
                } else {
                    // Create news entity
                    $news = new NewsItem();
                    $news->setStatus('draft');
                    $news->setActiveAt(new \DateTimeImmutable($activeDate));

                    // Handle file upload
                    $pictureFile = $request->files->get('picture');
                    if ($pictureFile) {
                        if (!$pictureFile->isValid()) {
                            $error = 'Ошибка загрузки файла';
                        } else {
                            // Create File entity for the uploaded picture
                            $file = new File();
                            $fileName = uniqid() . '.' . $pictureFile->guessExtension();
                            $file->setPath('uploads/news/' . $fileName);

                            // Create upload directory if it doesn't exist
                            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/news';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }

                            // Move the uploaded file to the target directory
                            $pictureFile->move($uploadDir, $fileName);

                            $em->persist($file); // Persist the File entity

                            // Create property value for image
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

                    // Create property values for title and text
                    if ($titleProperty) {
                        $titleValue = new PropertyValue();
                        $titleValue->setPropertyDefinition($titleProperty);
                        $titleValue->setSingleValue($title);
                        $titleValue->setNewsItem($news);
                        $news->addPropertyValue($titleValue);
                        $em->persist($titleValue);
                    }

                    if ($contentProperty) {
                        $textValue = new PropertyValue();
                        $textValue->setPropertyDefinition($contentProperty);
                        $textValue->setSingleValue($text);
                        $textValue->setNewsItem($news);
                        $news->addPropertyValue($textValue);
                        $em->persist($textValue);
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
            'titleProperty' => $titleProperty,
            'contentProperty' => $contentProperty,
            'imageProperty' => $imageProperty,
            'news' => [
                'title' => $title ?? '',
                'text' => $text ?? '',
                'activeAt' => $activeDate ?? '',
            ],
        ]);
    }

}
