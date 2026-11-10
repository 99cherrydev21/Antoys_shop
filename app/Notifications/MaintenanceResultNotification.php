<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaintenanceResultNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $requestId,
        public string $result,
        public string $message
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'maintenance_request_id' => $this->requestId,
            'result' => $this->result,
            'message' => $this->message,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
