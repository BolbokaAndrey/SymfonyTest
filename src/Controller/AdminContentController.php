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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\NotificationService;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/admin/content', name: 'admin_content')]
final class AdminContentController extends AbstractController
{
    private string $newsImageDirectory;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NewsRepository $newsRepository,
        private readonly PropertyValueRepository $propertyValueRepository,
        private readonly PropertyDefinitionRepository $propertyDefinitionRepository,
        private readonly SluggerInterface $slugger,
        private readonly NotificationService $notificationService,
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
    public function news(CacheInterface $cache): Response
    {
        $data = $cache->get('news', function (ItemInterface $item) {
            $item->expiresAfter(60);

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

            return $formattedNews;
        });


        return $this->render('admin_content/news.html.twig', [
            'title' => 'Новости',
            'news' => $data
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

            // Handle image upload
            $imageFile = $request->files->get('image');
            if ($imageFile instanceof UploadedFile) {
                $this->addFile($imageFile, $news);
            }

            // Handle tags
            $tags = $request->request->all('tags');
            $tags = array_filter($tags, function($tag) {
                return !empty(trim($tag));
            });
            $prop = new PropertyValue();
            $prop->setValue(implode(',', $tags));
            $prop->setPropertyDefinition($this->propertyDefinitionRepository->findOneBy(['code' => 'tags']));
            $this->entityManager->persist($prop);
            $news->addProperty($prop);

            // Handle source property
            $sourceValue = $request->request->get('source');
            if ($sourceValue) {
                $sourceDefinition = $this->propertyDefinitionRepository->findOneBy(['code' => 'source']);
                $property->setValue($sourceValue);
                $property->setPropertyDefinition($sourceDefinition);
                $news->addProperty($property);
                $this->entityManager->persist($property);
            }

            $this->entityManager->persist($news);
            $this->entityManager->flush();

            // Send notification about new news
            $this->notificationService->notifyAboutNewNews(
                $news->getId(),
                $news->getTitle(),
                $news->getCreatedAt()
            );

            $this->addFlash('success', 'Новость успешно создана');
            return $this->redirectToRoute('admin_content_news');
        }

        return $this->render('admin_content/news_form.html.twig', [
            'title' => 'Создание новости',
            'news' => $news
        ]);
    }

    #[Route('/news/{id}/edit', name: '_news_edit')]
    public function edit(int $id, Request $request): Response
    {
        $news = $this->newsRepository->find($id);
        if (!$news) {
            throw $this->createNotFoundException('Новость не найдена');
        }

        if ($request->isMethod('POST')) {
            $news->setTitle($request->request->get('title'));
            $news->setText($request->request->get('text'));
            $news->setCreatedAt(new \DateTimeImmutable($request->request->get('createdAt')));

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/news';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $imageFile = $request->files->get('image');
            if ($imageFile instanceof UploadedFile) {
                $existingImageProperty = $news->getProperties()->filter(function($property) {
                    return $property->getPropertyDefinition()->getCode() === 'image';
                })->first();

                if ($existingImageProperty) {
                    // Remove old image file if it exists
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/news/' . $existingImageProperty->getValue();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                    // Remove the property from the news and entity manager
                    $news->removeProperty($existingImageProperty);
                    $this->entityManager->remove($existingImageProperty);
                }

                $this->addFile($imageFile, $news);
            }

            $tagsValue = $request->request->all('tags');
            if ($tagsValue) {
                $news->getProperties()->filter(function ($property) {
                    return $property->getPropertyDefinition()->getCode() === 'tags';
                })->first()->setValue(implode(',', $tagsValue));
            }

            $sourceValue = $request->request->get('source');
            if ($sourceValue) {
                $news->getProperties()->filter(function ($property) {
                    return $property->getPropertyDefinition()->getCode() === 'source';
                })->first()->setValue($sourceValue);
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

    #[Route('/news/{id}/delete', name: '_news_delete')]
    public function delete(int $id): Response
    {
        $news = $this->newsRepository->find($id);

        if (!$news) {
            throw $this->createNotFoundException('Новость не найдена');
        }

        $this->entityManager->remove($news);
        $this->entityManager->flush();

        $this->addFlash('success', 'Новость успешно удалена');
        return $this->redirectToRoute('admin_content_news');
    }

    /**
     * @param UploadedFile $imageFile
     * @param $news
     * @return void
     */
    public function addFile(UploadedFile $imageFile, $news): void
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/news';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        try {
            $imageFile->move(
                $uploadDir,
                $newFilename
            );
            $prop = new PropertyValue();
            $prop->setValue($newFilename);
            $prop->setPropertyDefinition($this->propertyDefinitionRepository->findOneBy(['code' => 'image']));
            $this->entityManager->persist($prop);
            $news->addProperty($prop);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Не удалось загрузить изображение');
        }
    }
}
