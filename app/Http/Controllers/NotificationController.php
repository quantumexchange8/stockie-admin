<?php

namespace App\Http\Controllers;

use App\Models\IventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Log;

class NotificationController extends Controller
{
    public function index()
    {
        // Retrieve all notifications
        $notifications = auth()->user()->notifications;

        // Filter notifications by a specific keyword in the type
        $orderNotifications = $notifications->filter(function ($notification) {
            return str_contains($notification->type, 'Order');
        });

        foreach ($orderNotifications as $notification) {
            $orderData = $notification->data;
        
            if (isset($orderData['waiter_id'])) {
                $waiter = User::find($orderData['waiter_id']);
                $orderData['image'] = $waiter->getFirstMediaUrl('user');
                $notification->data = $orderData;
            }
        }

        $inventoryNotifications = $notifications->filter(function ($notification) {
            return str_contains($notification->type, 'Inventory');
        });

        foreach ($inventoryNotifications as $inventoryNotification) {
            $inventoryData = $inventoryNotification->data;

            if (isset($inventoryData['inventory_id'])) {
                // Find the inventory items by inventory_id
                $inventoryItems = IventoryItem::where('inventory_id', $inventoryData['inventory_id'])->get();
        
                $inventoryItems->each(function ($inventoryItem) use (&$inventoryData) {
                    // Loop through productItems for each inventoryItem
                    $inventoryItem->productItems->each(function ($productItem) use (&$inventoryData) {
                        // Access the related product
                        $product = $productItem->product;
        
                        if ($product) {
                            // Fetch the product image and assign to inventoryData
                            $inventoryData['image'] = $product->getFirstMediaUrl('product');
                        }
                    });
                });
        
                // Reassign the updated data back to the notification
                $inventoryNotification->data = $inventoryData;
            }
        }

        $waiterNotifications = $notifications->filter(function ($notification) {
            return str_contains($notification->type, 'Waiter');
        });

        return response()->json([
            'order_notifications' => $orderNotifications,
            'inventory_notifications' => $inventoryNotifications,
            'waiter_notifications' => $waiterNotifications,
        ]);
    }
}
