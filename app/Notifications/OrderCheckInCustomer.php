<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCheckInCustomer extends Notification
{
    use Queueable;
    private string $tableNo;
    private string $waiterName;
    private int $waiterID;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $tableNo, string $waiterName, int $waiterID)
    {
        //
        $this->tableNo = $tableNo;
        $this->waiterName = $waiterName;
        $this->waiterID = $waiterID;
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
            'waiter_name' => $this->waiterName,
            'table_no' => $this->tableNo,
            'waiter_id' => $this->waiterID,
        ];
    }

    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'OrderCheckInCustomer';
    }
}
