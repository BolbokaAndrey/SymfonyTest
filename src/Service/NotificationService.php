<?php

namespace App\Service;

use App\Entity\NewsItem;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $adminEmail = 'admin@example.com',
        private readonly string $appName = 'Symfony News Admin',
    ) {}

    /**
     * Send notification to admin when a new news item is created
     */
    public function notifyAdminAboutNewItem(NewsItem $newsItem, User $createdBy): void
    {
        $subject = sprintf('[%s] Новый элемент контента создан', $this->appName);

        $body = $this->createNotificationBody($newsItem, $createdBy, 'создан');

        $email = (new Email())
            ->from($this->adminEmail)
            ->to($this->adminEmail)
            ->subject($subject)
            ->html($body);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't throw exception to avoid breaking the main flow
            error_log('Failed to send notification email: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to admin when a news item is updated
     */
    public function notifyAdminAboutUpdatedItem(NewsItem $newsItem, User $updatedBy): void
    {
        $subject = sprintf('[%s] Элемент контента обновлен', $this->appName);

        $body = $this->createNotificationBody($newsItem, $updatedBy, 'обновлен');

        $email = (new Email())
            ->from($this->adminEmail)
            ->to($this->adminEmail)
            ->subject($subject)
            ->html($body);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't throw exception to avoid breaking the main flow
            error_log('Failed to send notification email: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to admin when a news item is deleted
     */
    public function notifyAdminAboutDeletedItem(int $itemId, string $itemTitle, User $deletedBy): void
    {
        $subject = sprintf('[%s] Элемент контента удален', $this->appName);

        $body = sprintf(
            '<h2>Элемент контента удален</h2>
            <p><strong>ID:</strong> %d</p>
            <p><strong>Название:</strong> %s</p>
            <p><strong>Удален пользователем:</strong> %s (%s)</p>
            <p><strong>Время удаления:</strong> %s</p>
            <hr>
            <p>Это уведомление отправлено автоматически системой.</p>',
            $itemId,
            htmlspecialchars($itemTitle),
            htmlspecialchars($deletedBy->getEmail()),
            htmlspecialchars($deletedBy->getEmail()),
            date('d.m.Y H:i:s')
        );

        $email = (new Email())
            ->from($this->adminEmail)
            ->to($this->adminEmail)
            ->subject($subject)
            ->html($body);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't throw exception to avoid breaking the main flow
            error_log('Failed to send notification email: ' . $e->getMessage());
        }
    }

    private function createNotificationBody(NewsItem $newsItem, User $user, string $action): string
    {
        $itemUrl = $this->urlGenerator->generate('admin_content_news', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $editUrl = $this->urlGenerator->generate('api_news_items_get', ['id' => $newsItem->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $propertiesHtml = '';
        foreach ($newsItem->getPropertiesAsArray() as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $propertiesHtml .= sprintf(
                '<tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>%s:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">%s</td></tr>',
                htmlspecialchars($key),
                htmlspecialchars(substr((string)$value, 0, 200)) . (strlen((string)$value) > 200 ? '...' : '')
            );
        }

        return sprintf(
            '<h2>Элемент контента %s</h2>
            <table style="border-collapse: collapse; width: 100%%; margin: 20px 0;">
                <tr>
                    <td style="padding: 12px; background: #f5f5f5; font-weight: bold;">ID:</td>
                    <td style="padding: 12px;">%d</td>
                </tr>
                <tr>
                    <td style="padding: 12px; background: #f5f5f5; font-weight: bold;">Статус:</td>
                    <td style="padding: 12px;">%s</td>
                </tr>
                <tr>
                    <td style="padding: 12px; background: #f5f5f5; font-weight: bold;">Создан:</td>
                    <td style="padding: 12px;">%s</td>
                </tr>
                <tr>
                    <td style="padding: 12px; background: #f5f5f5; font-weight: bold;">Обновлен:</td>
                    <td style="padding: 12px;">%s</td>
                </tr>
                <tr>
                    <td style="padding: 12px; background: #f5f5f5; font-weight: bold;">Активация:</td>
                    <td style="padding: 12px;">%s</td>
                </tr>
                <tr>
                    <td style="padding: 12px; background: #f5f5f5; font-weight: bold;">Действие:</td>
                    <td style="padding: 12px;">%s пользователем %s</td>
                </tr>
            </table>

            <h3>Свойства элемента:</h3>
            <table style="border-collapse: collapse; width: 100%%;">
                %s
            </table>

            <div style="margin: 30px 0;">
                <a href="%s" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; margin-right: 10px;">
                    Просмотреть все элементы
                </a>
                <a href="%s" style="background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                    Просмотреть элемент
                </a>
            </div>

            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
            <p style="color: #666; font-size: 14px;">
                Это уведомление отправлено автоматически системой %s.<br>
                Время отправки: %s
            </p>',
            $action,
            $newsItem->getId(),
            $newsItem->getStatus(),
            $newsItem->getCreatedAt()->format('d.m.Y H:i:s'),
            $newsItem->getUpdatedAt()->format('d.m.Y H:i:s'),
            $newsItem->getActiveAt() ? $newsItem->getActiveAt()->format('d.m.Y H:i:s') : 'Не установлена',
            $action,
            htmlspecialchars($user->getEmail()),
            $propertiesHtml,
            $itemUrl,
            $editUrl,
            $this->appName,
            date('d.m.Y H:i:s')
        );
    }
}
