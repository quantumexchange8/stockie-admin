<?php

namespace App\Http\Controllers;

use App\Models\IventoryItem;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Log;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications;

        $processedNotifications = $notifications->map(function ($notification) {
            $type = $notification->type;
    
            //type contains Inventory = Inventory type notification
            if (str_contains($type, 'Inventory')) {

                $inventoryData = $notification->data;
                if (str_contains($type, 'Inventory')) {
                    $inventoryData = $notification->data;
            
                    if (isset($inventoryData['inventory_id'])) {
                        $inventoryId = $inventoryData['inventory_id'];
            
                        // Fetch all product items related to the inventory item
                        $productItems = ProductItem::with('product.category')
                            ->where('inventory_item_id', $inventoryId)
                            ->get();
            
                        $inventoryData['product_image'] = [];
                        $inventoryData['redeem_item_image'] = [];
                        $inventoryData['categories'] = [];
            
                        foreach ($productItems as $productItem) {
                            // Add product images if available
                            if ($productItem->product) {
                                $productImage = $productItem->product->getFirstMediaUrl('product');
                                // if ($productImage) {
                                //     $inventoryData['product_image'][] = $productImage;
                                // }
                                $inventoryData['product_image'][] = $productImage ?? null;

            
                                // Add categories if available
                                if ($productItem->product->category) {
                                    $inventoryData['categories'][] = $productItem->product->category->name;
                                }
                            }
                            
                            // Add redeem item images
                            $redeemImage = $productItem->product->getFirstMediaUrl('product');
                            // if ($redeemImage) {
                            //     $inventoryData['redeem_item_image'][] = $redeemImage;
                            // }
                            $inventoryData['redeem_item_image'][] = $redeemImage ?? null;

                        }
            
                        // Assign back to notification
                        $notification->data = $inventoryData;
                    }
                }

            //type contains Waiter = Waiter check in check out notification
            } elseif (str_contains($type, 'Waiter')) {
                $notification->extra = 'This is waiter check in check out notification';

            //type contains Order = Table / Room Activities notification
            } elseif (str_contains($type, 'Order')) {
                $orderData = $notification->data;

                if(isset($orderData['waiter_id'])){
                    $waiter = User::find($orderData['waiter_id']);
                    if($waiter){
                        $orderData['waiter_image'] = $waiter->getFirstMediaUrl('user');
                        $orderData['waiter_name'] = $waiter->full_name;
                    }
                }
                if(isset($orderData['assigner_id'])){
                    $assigner = User::find($orderData['assigner_id']);
                    if($assigner){
                        $orderData['assigner_image'] = $assigner->getFirstMediaUrl('user');
                        $orderData['assigner_name'] = $assigner->full_name;
                    }
                }

                $notification->data = $orderData;
            } else {
                // Default action for unrecognized types
                $notification->extra = 'Unknown type';
            }
    
            return $notification; 
        });


        return Inertia::render("Notifications/Notifications", [
            'notifications' => $processedNotifications,
        ]);
    }

    public function filterNotification(Request $request)
    {
        $filterCategory = $request->input('checkedFilters.category', []);
        $filterDate = $request->input('checkedFilters.date', []);
        
        // Start a query on the user's notifications
        $query = auth()->user()->notifications();
        
        if (!empty($filterDate) && is_array($filterDate)) {
            // Single date filter
            if (count($filterDate) === 1) {
                $date = (new \DateTime($filterDate[0]))
                    ->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))
                    ->format('Y-m-d');
                $query->whereDate('created_at', $date);
            }
        
            // Range date filter
            elseif (count($filterDate) > 1) {
                $startDate = (new \DateTime($filterDate[0]))
                    ->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))
                    ->format('Y-m-d');
                $endDate = (new \DateTime($filterDate[1]))
                    ->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))
                    ->format('Y-m-d');
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }
        
        if (!empty($filterCategory) && is_array($filterCategory)) {
            $query->where(function ($subQuery) use ($filterCategory) {
                foreach ($filterCategory as $category) {
                    switch ($category) {
                        case 'Inventory':
                            $subQuery->orWhere('type', 'LIKE', '%Inventory%');
                            break;
                        case 'Waiter Check in / out':
                            $subQuery->orWhere('type', 'LIKE', '%Waiter%');
                            break;
                        case 'Table / Room Activity':
                            $subQuery->orWhere('type', 'LIKE', '%Order%');
                            break;
                    }
                }
            });
        }
        
        // Retrieve filtered notifications
        $filteredNotifications = $query->get();

        $processedNotifications = $filteredNotifications->map(function ($notification) {
            $type = $notification->type;
    
            //type contains Inventory = Inventory type notification
            if (str_contains($type, 'Inventory')) {

                $inventoryData = $notification->data;
                if (str_contains($type, 'Inventory')) {
                    $inventoryData = $notification->data;
            
                    if (isset($inventoryData['inventory_id'])) {
                        $inventoryId = $inventoryData['inventory_id'];
            
                        // Fetch all product items related to the inventory item
                        $productItems = ProductItem::with('product.category')
                            ->where('inventory_item_id', $inventoryId)
                            ->get();
            
                        $inventoryData['product_image'] = [];
                        $inventoryData['redeem_item_image'] = [];
                        $inventoryData['categories'] = [];
            
                        foreach ($productItems as $productItem) {
                            // Add product images if available
                            if ($productItem->product) {
                                $productImage = $productItem->product->getFirstMediaUrl('product');
                                // if ($productImage) {
                                //     $inventoryData['product_image'][] = $productImage;
                                // }
                                $inventoryData['product_image'][] = $productImage ?? null;

            
                                // Add categories if available
                                if ($productItem->product->category) {
                                    $inventoryData['categories'][] = $productItem->product->category->name;
                                }
                            }
                            
                            // Add redeem item images
                            $redeemImage = $productItem->product->getFirstMediaUrl('product');
                            // if ($redeemImage) {
                            //     $inventoryData['redeem_item_image'][] = $redeemImage;
                            // }
                            $inventoryData['redeem_item_image'][] = $redeemImage ?? null;

                        }
            
                        // Assign back to notification
                        $notification->data = $inventoryData;
                    }
                }

            //type contains Waiter = Waiter check in check out notification
            } elseif (str_contains($type, 'Waiter')) {
                $notification->extra = 'This is waiter check in check out notification';

            //type contains Order = Table / Room Activities notification
            } elseif (str_contains($type, 'Order')) {
                $orderData = $notification->data;

                if(isset($orderData['waiter_id'])){
                    $waiter = User::find($orderData['waiter_id']);
                    if($waiter){
                        $orderData['waiter_image'] = $waiter->getFirstMediaUrl('user');
                        $orderData['waiter_name'] = $waiter->full_name;
                    }
                }
                if(isset($orderData['assigner_id'])){
                    $assigner = User::find($orderData['assigner_id']);
                    if($assigner){
                        $orderData['assigner_image'] = $assigner->getFirstMediaUrl('user');
                        $orderData['assigner_name'] = $assigner->full_name;
                    }
                }

                $notification->data = $orderData;
            } else {
                // Default action for unrecognized types
                $notification->extra = 'Unknown type';
            }
    
            return $notification; 
        });
        
        return response()->json($processedNotifications);
        
    }

    public function latestNotification()
    {
        // Retrieve all notifications
        $notifications = auth()->user()->unreadNotifications;
        // $notifications = auth()->user()->notifications;

        // Filter notifications by a specific keyword in the type
        $orderNotifications = $notifications->filter(function ($notification) {
            return str_contains($notification->type, 'Order');
        })->take(10);

        foreach ($orderNotifications as $notification) {
            $orderData = $notification->data;

            if(isset($orderData['waiter_id'])){
                $waiter = User::find($orderData['waiter_id']);
                if($waiter){
                    $orderData['waiter_image'] = $waiter->getFirstMediaUrl('user');
                    $orderData['waiter_name'] = $waiter->full_name;
                }
            }
            if(isset($orderData['assigner_id'])){
                $assigner = User::find($orderData['assigner_id']);
                if($assigner){
                    $orderData['assigner_image'] = $assigner->getFirstMediaUrl('user');
                    $orderData['assigner_name'] = $assigner->full_name;
                }
            }

            $notification->data = $orderData;
        }

        $inventoryNotifications = $notifications->filter(function ($notification) {
            return str_contains($notification->type, 'Inventory');
        })->take(10);

        foreach ($inventoryNotifications as $inventoryNotification) {
            $inventoryData = $inventoryNotification->data;

            if (isset($inventoryData['inventory_id'])) {
                // Find the inventory items by inventory_id
                $inventoryItems = IventoryItem::where('id', $inventoryData['inventory_id'])->get();
        
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
        })->take(10);

        // auth()->user()->unreadNotifications->markAsRead();
        $allNotifications = $orderNotifications->count() + $inventoryNotifications->count() + $waiterNotifications->count();

        return response()->json([
            'order_notifications' => $orderNotifications,
            'inventory_notifications' => $inventoryNotifications,
            'waiter_notifications' => $waiterNotifications,
            'all_notifications' => $allNotifications
        ]);
    }

    public function markAsRead(Request $request)
    {
        $notifications = $request->input('notifications');

        // Ensure the notifications are valid
        $notificationIds = collect($notifications)->pluck('id');

        // Mark notifications as read
        auth()->user()->notifications()->whereIn('id', $notificationIds)->update(['read_at' => now()]);

    }

}
