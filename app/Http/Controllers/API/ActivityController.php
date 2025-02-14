<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
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
}
