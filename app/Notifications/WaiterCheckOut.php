<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaiterCheckOut extends Notification
{
    use Queueable;

    private $waiter;
    private $checkOut;

    /**
     * Create a new notification instance.
     */
    public function __construct($waiter, $checkOut)
    {
        $this->waiter = $waiter;
        $this->checkOut = $checkOut;
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
    public function toDatabase(object $notifiable): array
    {
        return [
            'data' => 'Checked out at ' . $this->checkOut . '.'
        ];
    }
    
    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'WaiterCheckOut';
    }
}
