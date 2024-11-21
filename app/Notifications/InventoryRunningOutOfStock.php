<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InventoryRunningOutOfStock extends Notification
{
    use Queueable;

    private string $inventoryName;
    private int $inventoryID;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $inventoryName, int $inventoryID)
    {
        $this->inventoryName = $inventoryName;
        $this->inventoryID = $inventoryID;
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
            'inventory_name' => $this->inventoryName,
            'inventory_id' => $this->inventoryID,
        ];
    }

    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'InventoryRunningOutOfStock';
    }
}
