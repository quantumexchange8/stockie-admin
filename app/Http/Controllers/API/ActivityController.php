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
        $processedNotifications = $notifications
            ->filter(fn ($notification) => str_contains($notification->type, 'Order'))
            ->take(20)
            ->map(function ($notification) {
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
        
                return $notification; 
            });

        return [
            'notifications_count' => $processedNotifications->count(),
            'notifications' => $processedNotifications
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
            if ($this->authUser->unreadNotifications->count() > 0) {
                // Mark all unread notifications as read
                $this->authUser->unreadNotifications()
                    // ->where('type', 'like', '%Order%')
                    ->update(['read_at' => now()]);
            }

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
