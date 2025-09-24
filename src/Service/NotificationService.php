<?php

namespace App\Service;

use App\Message\NewsCreatedNotification;
use Symfony\Component\Messenger\MessageBusInterface;

class NotificationService
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function notifyAboutNewNews(int $newsId, string $title, \DateTimeInterface $createdAt): void
    {
        $notification = new NewsCreatedNotification(
            $newsId,
            $title,
            $createdAt->format('Y-m-d H:i:s')
        );

        $this->messageBus->dispatch($notification);
    }
}
