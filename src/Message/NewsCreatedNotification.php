<?php

namespace App\Message;

class NewsCreatedNotification
{
    private int $newsId;
    private string $title;
    private string $createdAt;

    public function __construct(int $newsId, string $title, string $createdAt)
    {
        $this->newsId = $newsId;
        $this->title = $title;
        $this->createdAt = $createdAt;
    }

    public function getNewsId(): int
    {
        return $this->newsId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
