<?php

namespace App\Notifications;

use App\Models\Badge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BadgeEarned extends Notification
{
    use Queueable;

    public function __construct(public Badge $badge) {}

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
            'icon' => $this->badge->icon,
            'title' => "¡Nueva insignia: {$this->badge->name}!",
            'message' => $this->badge->description,
            'url' => route('achievements.index'),
        ];
    }
}
