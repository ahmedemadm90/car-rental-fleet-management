<?php

namespace App\Notifications;

use App\Models\Car;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class VehicleReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Car $car,
        private readonly string $kind,
        private readonly string $title,
        private readonly string $body,
        private readonly string $severity = 'warning',
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('notifications.fcm_enabled') && $notifiable->pushTokens()->exists()) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
            'severity' => $this->severity,
            'car_id' => $this->car->id,
            'car_name' => "{$this->car->make} {$this->car->model}",
            'reminder_key' => implode(':', [$this->kind, $this->car->id, now()->toDateString()]),
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return (new FcmMessage(
            notification: new FcmNotification(
                title: $this->title,
                body: $this->body,
            ),
        ))->data([
            'kind' => $this->kind,
            'car_id' => (string) $this->car->id,
            'screen' => 'notifications',
            'severity' => $this->severity,
        ]);
    }
}
