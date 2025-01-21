<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ArticleNotification extends Notification
{
    use Queueable;

    public $article;

    public function __construct($article)
    {
        $this->article = $article;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $country = session('country', 'jordan');
        return [
            'title' => 'New Article: ' . $this->article->title,
            'article_id' => $this->article->id,
            'url' => route('dashboard.articles.show', ['article' => $this->article->id, 'country' => $country]),
        ];
    }
}
