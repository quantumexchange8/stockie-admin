<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    protected $authUser;
    public function __construct()
    {
        $this->authUser = User::find(Auth::id());
    }

    public function getRecentActivityLogs()
    {
        $logs = Activity::where('event', 'assign to serve')
                        ->orderBy('created_at', 'desc')
                        ->limit(3)
                        ->get()
                        ->filter(fn ($log) => (
                            $log->properties['waiter_name'] === Auth::user()->full_name
                        ));


        return response()->json($logs);
    }

    public function getAllActivityLogs()
    {
        $logs = Activity::where('event', 'assign to serve')
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->filter(fn ($log) => (
                            $log->properties['waiter_name'] === Auth::user()->full_name
                        ));

        return response()->json($logs);
    }

    private function processNotifications($notifications)
    {
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

        return [
            'notifications' => $processedNotifications,
            'notifications_count' => $processedNotifications->count()
        ];
    }
    
    // Retrieve all user notifications
    public function getNotifications()
    {
        $notifications = $this->authUser->notifications;

        $processedNotifications = $this->processNotifications($notifications);

        return response()->json($processedNotifications);
    }

    // Retrieve all user unread notifications
    public function getUnreadNotifcations()
    {
        $notifications = auth()->user()->unreadNotifications;

        $processedNotifications = $this->processNotifications($notifications);

        return response()->json($processedNotifications);
    }

    public function markUnreadNotifications()
    {
        try {
            // Mark all unread notifications as read
            $this->authUser->unreadNotifications()->update(['read_at' => now()]);

            return response()->json([
                'status' => 'success',
                'title' => "All unread notifications has been successfully read.",
            ], 201);
                
        } catch (\Exception  $e) {
            Log::error('Error marking unread notifications as read: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'title' => 'Error marking unread notifications as read.',
                'errors' => $e
            ], 422);
        };
    }
}
