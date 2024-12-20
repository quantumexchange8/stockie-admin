<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ConfigPromotion;
use App\Models\User;
use App\Models\WaiterAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // protected $user;
    
    // public function __construct()
    // {
    //     $this->user = User::find(2);
    // }
    
    /**
     * Get the user's current clock in time if 
     */
    public function getClockInTime()
    {
        $user = User::find(2);
        
        $latestCheckedIn = $user->attendances()
                                        ->where('status', 'Checked in')
                                        ->latest('check_in')
                                        ->first();

        $response = [
            'is_clocked_in' => $latestCheckedIn !== null,
            'clocked_in_at' => $latestCheckedIn?->check_in
        ];

        return response()->json($response);
    }

    /**
     * Get the user's attendance for today 
     */
    public function getTodayAttendance()
    {
        $user = User::find(2);
        $today = today()->toDateString();
    
        $todayAttendance = $user->attendances()
                                        ->where(function ($query) use ($today) {
                                            // Clock in on today's date
                                            $query->whereDate('check_in', $today)
                                                    // OR clock in before today but check out after today's start
                                                    ->orWhere(function ($subQuery) use ($today) {
                                                        $subQuery->whereDate('check_in', '<', $today)
                                                                ->whereDate('check_out', '>=', $today);
                                                    });
                                        })
                                        ->orderByDesc('check_in')
                                        ->first();

        $response = [
            'has_attendance' => !!$todayAttendance,
            'clocked_in_at' => $todayAttendance?->check_in,
            'clocked_out_at' => $todayAttendance?->check_out
        ];

        return response()->json($response);
    }
    
    /**
     * Get all of the user's attendances 
     */
    public function getAllAttendances(Request $request)
    {
        $query = WaiterAttendance::query();

        // $query = $user->attendances;
    
        // If start and end dates are provided, filter the attendances
        if ($request->has(['start_date', 'end_date'])) {
            $query->whereDate('check_in', '>=', $request->start_date)
                    ->whereDate('check_in', '<=', $request->end_date);
        }
    
        // Order and retrieve attendances
        $allAttendances = $query->where('user_id', 2)
                                ->orderBy('check_in', 'desc')
                                ->get(['check_in', 'check_out']);
    
        // Group attendances by date
        // $groupedAttendances = $query->orderBy('check_in', 'desc')
                                    // ->groupBy(fn ($attendance) => $attendance->check_in->format('d/m/Y'))
                                    // ->get();

        $response = [
            'has_attendance' => !!$allAttendances,
            'attendances' => $allAttendances ?? [],
        ];

        return response()->json($response);
    }
    
    /**
     * Get all of the user's attendances 
     */
    public function getAllPromotions(Request $request)
    {
        $allPromotions = ConfigPromotion::where('status', 'Active')
                        ->get()
                        ->map(function ($promotion) {
                            $promotion->promotion_image = $promotion->getFirstMediaUrl('promotion');
                            return $promotion;
                        });

        $response = [
            'promotions' => $allPromotions ?? [],
        ];

        return response()->json($response);
    }
}
