<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaiterCheckIn extends Notification
{
    use Queueable;

    private $waiterId;
    private $checkIn;

    /**
     * Create a new notification instance.
     */
    public function __construct($waiterId, $checkIn)
    {
        $this->waiterId = $waiterId;
        $this->checkIn = $checkIn;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'waiter_id' => $this->waiterId,
            'check_in' => $this->checkIn,
        ];
    }
    
    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'WaiterCheckIn';
    }
}
