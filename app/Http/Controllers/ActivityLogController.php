<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(){
        $logs = Activity::whereIn('event', ['added', 'updated', 'deleted', 'cancelled', 'kept', 'redeemed', 'submitted', 'refunded'])
                            ->whereNot('log_name', '=', 'default')
                            ->orderBy('created_at', 'desc')
                            ->get();

        return Inertia::render('ActivityLog/ActivityLog', [
            'logs' => $logs,
        ]);
    }

    public function filterLogs(Request $request) 
    {
        $dateFilter = $request->input('date_filter');
        $events = $request->input('checkedFilters.action') ?? ['added', 'updated', 'deleted', 'cancelled', 'kept', 'redeemed', 'submitted', 'refunded'];
        $dateFilter = array_map(function ($date){
            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d H:i:s');
        }, $dateFilter);

        $data = Activity::whereDate('created_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0])
                        ->when(count($dateFilter) > 1, function ($subQuery) use ($dateFilter) {
                            $subQuery->whereDate('created_at', '<=', $dateFilter[1]);
                        })
                        ->whereIn('event', $events)
                        ->whereNot('log_name', '=', 'default')
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json($data);
    }
}
