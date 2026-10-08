<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisos de grupos de estudio: nuevos miembros y cuestionarios compartidos.
 */
class GroupActivity extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $url,
        public string $icon = '👥',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => $this->icon,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
