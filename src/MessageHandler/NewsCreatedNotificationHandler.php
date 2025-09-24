<?php

namespace App\MessageHandler;

use App\Message\NewsCreatedNotification;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class NewsCreatedNotificationHandler
{
    private MailerInterface $mailer;
    private string $adminEmail;

    public function __construct(MailerInterface $mailer, string $adminEmail)
    {
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    public function __invoke(NewsCreatedNotification $notification): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($this->adminEmail)
            ->subject('Новая новость: ' . $notification->getTitle())
            ->html($this->getEmailContent($notification));

        $this->mailer->send($email);
    }

    private function getEmailContent(NewsCreatedNotification $notification): string
    {
        return sprintf(
            '<h1>Добавлена новая новость</h1>' .
            '<p><strong>ID:</strong> %d</p>' .
            '<p><strong>Заголовок:</strong> %s</p>' .
            '<p><strong>Дата создания:</strong> %s</p>',
            $notification->getNewsId(),
            htmlspecialchars($notification->getTitle()),
            htmlspecialchars($notification->getCreatedAt())
        );
    }
}
