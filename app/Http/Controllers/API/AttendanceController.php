<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WaiterAttendance;
use App\Models\WaiterShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;

class AttendanceController extends Controller
{
    protected $authUser;
    
    public function __construct()
    {
        $this->authUser = User::where('id', Auth::id())->first();
    }

    /**
     * Get the user's current clock in time if 
     */
    public function getCheckInTime()
    {
        $latestCheckedIn = $this->authUser->attendances()
                                            ->where('status', 'Checked in')
                                            ->latest('check_in')
                                            ->first();

        $response = [
            'is_checked_in' => $latestCheckedIn !== null,
            'check_in' => $latestCheckedIn?->check_in
        ];

        return response()->json($response);
    }

    /**
     * Get the user's attendance for today 
     */
    public function getTodayAttendance()
    {
        // $today = today()->toDateString();
    
        // $todayAttendance = $this->authUser->attendances()
        //                                     ->where(function ($query) use ($today) {
        //                                         // Clock in on today's date
        //                                         $query->whereDate('check_in', $today)
        //                                                 // OR clock in before today but check out after today's start
        //                                                 ->orWhere(function ($subQuery) use ($today) {
        //                                                     $subQuery->whereDate('check_in', '<', $today)
        //                                                             ->whereDate('check_out', '>=', $today);
        //                                                 });
        //                                     })
        //                                     ->orderByDesc('check_in')
        //                                     ->first();

        // $response = [
        //     'has_attendance' => !!$todayAttendance,
        //     'check_in' => $todayAttendance?->check_in,
        //     'check_out' => $todayAttendance?->check_out
        // ];

        
        $waiter = $this->authUser;
        
        // Get records where:
        // 1. Checked in today (regardless of check-out time), OR
        // 2. Checked out today or after midnight but still part of today's shift

        // $existingAttendance = $this->checkExistingAttendance($waiter, 'out');
        $existingAttendance = $waiter->latestAttendance()->first(['id', 'check_in', 'status']);

        // If no records found
        if (!$existingAttendance) {
            return response()->json([
                'message' => 'No attendance records found',
                'data' => []
            ]);
        }

        $latestCheckedIn = $existingAttendance->check_in;

        $records = WaiterAttendance::where('user_id', $waiter->id)
                                    ->where('check_in', '>=', $latestCheckedIn)
                                    ->orderByRaw("
                                        CASE 
                                            WHEN status IN ('Break end', 'Checked out') THEN check_out 
                                            ELSE check_in 
                                        END
                                    ")
                                    ->get();
        
        // If no records found
        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No attendance records found',
                'data' => []
            ]);
        }
        
        // Structure the response
        $response = [
            'check_in' => null,
            'check_out' => null,
            'breaks' => [],
            // 'is_overnight' => false
        ];
        
        $currentBreak = null;
        
        foreach ($records as $record) {
            switch ($record->status) {
                case 'Checked in':
                    $response['check_in'] = $record->check_in;

                    break;
                    
                case 'Checked out':
                    $response['check_out'] = $record->check_out;

                    break;
                    
                case 'Break start':
                    $currentBreak = [
                        'start' => $record->check_in,
                        'end' => null,
                    ];
                    break;
                    
                case 'Break end':
                    if ($currentBreak) {
                        $currentBreak['end'] = $record->check_out;
                        $response['breaks'][] = $currentBreak;
                        $currentBreak = null;
                    }

                    break;
            }
        }
        
        // Handle ongoing break
        if ($currentBreak) {
            $currentBreak['end'] = null;
            $response['breaks'][] = $currentBreak;
        }

        return response()->json($response);
    }
    
    /**
     * Get all of the user's attendances 
     */
    public function getAllAttendances(Request $request)
    {
        $waiter = $this->authUser;

        $dateFilter = array_map(
            fn($date) => Carbon::parse($date)
                ->timezone('Asia/Kuala_Lumpur')
                ->toDateString(),
            $request->input('date_filter')
        );
    
        $startDate = Carbon::parse($dateFilter[0])->startOfDay();
        $endDate = Carbon::parse($dateFilter[1] ?? $dateFilter[0])->endOfDay();
    
        // Get all records sorted chronologically
        $records = WaiterAttendance::whereDate('created_at', '>=', $startDate)
                                    ->whereDate('created_at', '<=', $endDate)
                                    ->where('user_id', $waiter->id)
                                    ->orderByRaw("
                                        CASE 
                                            WHEN status IN ('Break end', 'Checked out') THEN check_out 
                                            ELSE check_in 
                                        END
                                    ")
                                    ->get(['id', 'user_id', 'check_in', 'check_out', 'status', 'created_at']);
    
        // Early return if no records
        if ($records->isEmpty()) {
            return response()->json([]);
        }

        $salary = $waiter->salary ?? 0.00;
        // $totals = ['work' => 0, 'break' => 0, 'earnings' => 0];
        $attendanceGroups = [];
        $currentGroup = null;
    
        // Group records into complete attendance periods
        foreach ($records as $record) {
            switch ($record->status) {
                case 'Checked in':
                    if ($currentGroup) {
                        $attendanceGroups[] = $currentGroup;
                    }
                    $currentGroup = [
                        'check_in' => $record->check_in,
                        'check_out' => null,
                        'breaks' => [],
                        'date' => Carbon::parse($record->check_in)->format('d/m/Y'),
                        'status' => 'Ongoing'
                    ];
                    break;
                    
                case 'Checked out':
                    if ($currentGroup) {
                        $currentGroup['check_out'] = $record->check_out;
                        $currentGroup['status'] = 'Completed';
                        $attendanceGroups[] = $currentGroup;
                        $currentGroup = null;
                    }
                    break;
                    
                case 'Break start':
                case 'Break end':
                    if ($currentGroup) {
                        $currentGroup['breaks'][] = $record;
                    }
                    break;
            }
        }
    
        // Add the last ongoing group if exists
        if ($currentGroup) {
            $attendanceGroups[] = $currentGroup;
        }

        // Process groups in parallel using collection methods
        $groups = collect($attendanceGroups)->map(function($group) use ($salary, $waiter) {
            return $this->processAttendanceGroup($group, $salary, $waiter);
        })->sortByDesc('check_in')->values();
        return response()->json($groups);
    }

    // New helper function to format as "Xh Ym"
    protected function formatHoursMinutes($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return sprintf('%dh %02dm', $hours, $minutes);
    }

    private function processAttendanceGroup(array $group, float $salary, User $waiter): array
    {
        $endTime = $group['check_out'] ?? now();
        $workDuration = Carbon::parse($group['check_in'])->diffInSeconds($endTime);
        
        // Process breaks
        $breakData = $this->calculateBreakDurations($group['breaks'], $endTime);
        
        // Calculate earnings
        $payableHours = floor(max(0, $workDuration) / 60) / 60;
        $earnings = $waiter->employment_type === 'Part-time' 
            ? max(0, $payableHours * $salary) 
            : 0.00;
    
        return [
            'date' => $group['date'],
            'check_in' => $group['check_in'],
            'check_out' => $group['check_out'],
            'status' => $group['status'],
            'work_duration' => $this->formatHoursMinutes($workDuration),
            'break_duration' => $this->formatHoursMinutes($breakData['breakDuration']),
            'earnings' => 'RM '.number_format($earnings, 2),
            'break' => $breakData['formattedBreaks']
        ];
    }
    
    private function calculateBreakDurations(array $breaks, $endTime): array
    {
        $breakDuration = 0;
        $formattedBreaks = [];
        $currentBreak = null;
        
        // Sort breaks once
        $sortedBreaks = collect($breaks)->sortBy('check_in');
        
        foreach ($sortedBreaks as $break) {
            if ($break->status === 'Break start') {
                if ($currentBreak) {
                    // Handle unclosed break
                    $breakDuration += Carbon::parse($currentBreak['start'])
                        ->diffInSeconds($break->check_in);
                }
                $currentBreak = ['start' => $break->check_in, 'end' => null];
            } 
            elseif ($break->status === 'Break end' && $currentBreak) {
                $currentBreak['end'] = $break->check_out;
                $breakDuration += Carbon::parse($currentBreak['start'])
                    ->diffInSeconds($currentBreak['end']);
                $formattedBreaks[] = $currentBreak;
                $currentBreak = null;
            }
        }
        
        // Handle last unclosed break
        if ($currentBreak) {
            $currentBreak['end'] = null;
            $breakDuration += Carbon::parse($currentBreak['start'])
                ->diffInSeconds($endTime);
            $formattedBreaks[] = $currentBreak;
        }
        
        return [
            'breakDuration' => $breakDuration,
            'formattedBreaks' => $formattedBreaks
        ];
    }

    /**
     * Clock in waiter 
     */
    public function checkIn(Request $request)
    {
        // First check recent attendance and return if needed
        // $recentAttendance = $this->checkRecentAttendance($this->authUser);
        // if ($recentAttendance) {  // If there's recent attendance
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => "If you meant to clock back in, confirm to get started again.",
        //     ], 402);
        // }
        $waiter = $this->authUser;
        
        try {
            $validatedData = $request->validate(
                [
                    'passcode' => 'required|integer|min_digits:6|max_digits:6',
                    'check_in' => 'required|date_format:Y-m-d H:i:s'
                ], 
                [
                    'required' => 'This field is required.',
                    'integer' => 'This field must be an integer.',
                    'min_digits' => 'This field must have a minimum of 6 digits.',
                    'max_digits' => 'This field must have a maximum of 6 digits.',
                ]
            );

            $checkInTime = Carbon::parse($validatedData['check_in']);
                    
            // $existingAttendance = $this->checkExistingAttendance($waiter, 'in');
            $existingAttendance = $waiter->latestAttendance()->first(['id', 'check_in', 'status']);
            if ($existingAttendance && ($existingAttendance->status === 'Checked in' || Carbon::parse($existingAttendance->check_in)->isToday())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are already checked in for today.',
                ], 422);
            }

            // Case 1: No passcode exists
            if (is_null($waiter->passcode)) {
                return $this->handleNewPasscode($waiter, $checkInTime);
            }

            // Check if account is locked
            if ($waiter->passcode_status === 'Locked') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your account is locked. Please contact an administrator to reset your passcode.',
                ], 423); // 423 Locked HTTP status code
            }

            // // Check rate limiting
            // if ($this->isRateLimited($validatedData['passcode'])) {
            //     $seconds = RateLimiter::availableIn($this->throttleKey($validatedData['passcode']));
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => "Too many failed attempts. Please try again in $seconds seconds.",
            //     ], 429);
            // }

            // Case 2: Verify existing passcode
            if ($waiter->passcode !== $validatedData['passcode']) {
                // RateLimiter::hit($this->throttleKey($validatedData['passcode']));
                return $this->handleFailedAttempt($waiter, $validatedData['passcode']);
            }

            return $this->handleSuccessfulCheckIn($waiter, $checkInTime);

        }catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            return response()->json([
                'status' => 'Error checking in.',
                'errors' => $e
            ]);
        }
    }

    /**
     * Manage waiter's break start/end 
     */
    public function handleBreak(Request $request)
    {
        $request->validate([
            'action' => 'required|in:start,end'
        ]);
    
        $waiter = $this->authUser;
        $action = $request->input('action');
    
        if ($action === 'start') {
            return $this->startBreak($waiter);
        } else {
            return $this->endBreak($waiter);
        }
    }

    protected function startBreak($waiter)
    {
        // Verify waiter is checked in
        $lastAttendance = WaiterAttendance::where('user_id', $waiter->id)
                                            ->whereNotNull('check_in')
                                            ->where(function($query) {
                                                $query->whereNull('check_out')
                                                    ->orWhereNotNull('check_out')
                                                    ->orWhere('status', 'Break end');
                                            })
                                            ->latest()
                                            ->first();
    
        if (!$lastAttendance || !in_array($lastAttendance->status, ['Checked in', 'Break end'])) {
            return response()->json([
                'message' => 'Cannot start break - invalid current status'
            ], 400);
        }
        
        $breakRecord = WaiterAttendance::create([
            'user_id' => $waiter->id,
            'check_in' => now(),
            'status' => 'Break start'
        ]);
    
        return response()->json([
            'message' => 'Break started successfully',
            'data' => $breakRecord
        ], 201);
    }
    
    protected function endBreak($waiter)
    {
        // Find the latest break start without end
        $breakStartRecord = WaiterAttendance::where('user_id', $waiter->id)
                                        ->whereNotNull('check_in')
                                        ->where(function($query) {
                                            $query->whereNotNull('check_out')
                                                ->orWhere('status', 'Break start');
                                        })
                                        ->latest()
                                        ->first();
    
        if (!$breakStartRecord || $breakStartRecord->status !== 'Break start') {
            return response()->json([
                'message' => 'Cannot end break - invalid current status'
            ], 400);
        }

        $breakRecord = WaiterAttendance::create([
            'user_id' => $waiter->id,
            'check_in' => $breakStartRecord->check_in,
            'check_out' => now(),
            'status' => 'Break end'
        ]);
    
        return response()->json([
            'message' => 'Break ended successfully',
            'data' => $breakRecord
        ], 201);
    }

    /**
     * Check for recent attendance if waiter has logged out recently within an hour then will return confirmation message
     */
    // private function checkRecentAttendance(User $user)
    // {
    //     return $user->attendances()
    //                 ->where('status', 'Checked out')
    //                 ->whereDate('check_out', '>=', now()->subMinutes(60))
    //                 ->whereDate('check_out', '<=', now())
    //                 ->first();
    // }

    /**
     * Check for existing attendance
     */
    // private function checkExistingAttendance(User $user, string $action)
    // {
    //     return $user->attendances()
    //                 // ->when($action === 'in', fn ($subQuery) => $subQuery->whereNotNull('check_in'))
    //                 ->where(function($query) {
    //                     $query->where('status', 'Checked in')
    //                         ->orWhere('status', 'Checked out');
    //                 })
    //                 ->latest()
    //                 ->first(['id', 'check_in', 'status']);
    // }

    /**
     * Handle new passcode generation and check-in
     */
    private function handleNewPasscode(User $user, Carbon $checkInTime): \Illuminate\Http\JsonResponse
    {
        $newPasscode = $this->generateUniquePasscode();
        
        $user->update(['passcode' => $newPasscode]);
        $attendance = $this->createAttendance($user, $checkInTime);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Check-in successful. New passcode generated.',
            'data' => [
                'passcode' => $newPasscode,
                'check_in' => $attendance->check_in,
            ]
        ], 201);
    }

    /**
     * Handle failed passcode attempt
     */
    private function handleFailedAttempt(User $user, $passcode)
    {
        // Increment failed attempts
        $failedAttempts = RateLimiter::attempts($this->throttleKey($passcode)) + 1;
        RateLimiter::hit($this->throttleKey($passcode));

        if ($failedAttempts >= 3) {
            // Lock the account
            $user->update(['passcode_status' => 'Locked']);

            // Clear rate limiter as we're now using account lock
            RateLimiter::clear($this->throttleKey($passcode));

            return response()->json([
                'status' => 'error',
                'message' => 'Your account has been locked due to too many failed attempts. Please contact an administrator.',
            ], 423);
        }

        $remainingAttempts = 3 - $failedAttempts;
        return response()->json([
            'status' => 'error',
            'message' => "Wrong passcode, you've $remainingAttempts more attempts",
        ], 401);
    }

    /**
     * Handle successful check-in
     */
    private function handleSuccessfulCheckIn(User $user, Carbon $checkInTime): \Illuminate\Http\JsonResponse
    {
        RateLimiter::clear($this->throttleKey($user->passcode));
        $attendance = $this->createAttendance($user, $checkInTime);

        return response()->json([
            'status' => 'success',
            'message' => "Are you ready to tackle the day? Let's make it a great one!",
            'check_in' => $attendance->check_in,
        ], 201);
    }

    // /**
    //  * Check if the request is rate limited
    //  */
    // private function isRateLimited($passcode): bool
    // {
    //     return RateLimiter::tooManyAttempts($this->throttleKey($passcode), 3);
    // }

    /**
     * Get the rate limiting throttle key for the clock in request.
     */
    public function throttleKey($passcode): string
    {
        return 'check-in:' . hash('sha256', $passcode);
    }


    /**
     * Generate a unique 6-digit passcode
     */
    private function generateUniquePasscode()
    {
        do {
            $newPasscode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $exists = User::where('passcode', $newPasscode)->exists();
        } while ($exists);

        return $newPasscode;
    }
    
    /**
     * Create a new attendance record
     */
    private function createAttendance(User $user, $checkedInAt)
    {
        return $user->attendances()->create([
            'check_in' => $checkedInAt,
            'check_out' => null,
            'status' => 'Checked in',
        ]);
    }

    /**
     * Authenticate old passcode 
     */
    public function authenticateOldPasscode(Request $request)
    {
        try {
            $validatedData = $request->validate(
                ['passcode' => 'required|integer|min_digits:6|max_digits:6'], 
                [
                    'required' => 'This field is required.',
                    'integer' => 'This field must be an integer.',
                    'min_digits' => 'This field must have a minimum of 6 digits.',
                    'max_digits' => 'This field must have a maximum of 6 digits.',
                ]
            );

            // Verify existing passcode
            if ($this->authUser->passcode !== $validatedData['passcode']) {
                return $this->handleFailedAttempt($this->authUser, $validatedData['passcode']);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Authentication successful.',
            ], 201);

        }catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            return response()->json([
                'status' => 'Error checking in.',
                'errors' => $e
            ]);
        }
    }

    /**
     * Change new passcode 
     */
    public function changeNewPasscode(Request $request)
    {
        try {
            $validatedData = $request->validate(
                ['passcode' => 'required|integer|min_digits:6|max_digits:6'], 
                [
                    'required' => 'This field is required.',
                    'integer' => 'This field must be an integer.',
                    'min_digits' => 'This field must have a minimum of 6 digits.',
                    'max_digits' => 'This field must have a maximum of 6 digits.',
                ]
            );

            $this->authUser->update(['passcode' => $validatedData['passcode']]);
            
            return response()->json([
                'status' => 'success',
                'message' => "You've successfully changed your passcode.",
            ], 201);

        }catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            return response()->json([
                'status' => 'Error checking in.',
                'errors' => $e
            ]);
        }
    }

    /**
     * Clock out waiter 
     */
    public function checkOut(Request $request)
    {
        $waiter = $this->authUser;
        // $existingAttendance = $this->checkExistingAttendance($waiter, 'out');
        $existingAttendance = $waiter->latestAttendance()->first(['id', 'check_in', 'status']);

        if ($existingAttendance && $existingAttendance->status === 'Checked in') {
            $checkOutAt = now();
            $attendance = WaiterAttendance::create([
                'user_id' => $waiter->id,
                'check_in' => $existingAttendance->check_in,
                'check_out' => $checkOutAt,
                'status' => 'Checked out'
            ]);

            $shiftDuration = date_diff(Carbon::parse($attendance->check_in), $checkOutAt);
            $hoursWorked = sprintf('%02d', $shiftDuration->h);
            $surplasMinsWorked = sprintf('%02d', $shiftDuration->i);
            
            return response()->json([
                'status' => 'success',
                'message' => "You've worked {$hoursWorked} hrs {$surplasMinsWorked} mins today. Time to relax and recharge—you've earned it!",
                'check_out' => $checkOutAt,
            ], 201);
        }

        return response()->json([
            'status' => 'error',
            'message' => "You are currently not clocked in.",
        ], 422);
    }

    

    /**
     * Redeem entry reward and add to current order.
     */
    public function getWeeklyShifts()
    {
        try {
            $waiterShifts = WaiterShift::query()
                                    ->where('waiter_id', $this->authUser->id)
                                    ->where('weeks', now()->weekOfYear)
                                    ->with(['users:id,name,full_name', 'shifts'])
                                    ->get();

            return response()->json([
                'status' => 'success',
                'waiter_shifts' => $waiterShifts,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'title' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            return response()->json([
                'title' => 'Error getting weekly shifts.',
                'errors' => $e
            ], 422);
        };
    }

    /**
     * Redeem entry reward and add to current order.
     */
    public function getAllShifts()
    {
        try {
            $waiterShifts = WaiterShift::with(['users:id,name,full_name', 'shifts'])->get();

            $waiterShifts->each(function ($shift) {
                if ($shift->users) {
                    $shift->users->image = $shift->users->getFirstMediaUrl('user');
                }
            });

            return response()->json([
                'status' => 'success',
                'waiter_shifts' => $waiterShifts,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'title' => 'The given data was invalidW.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            return response()->json([
                'title' => 'Error getting all waiter shifts.',
                'errors' => $e
            ], 422);
        };
    }
}
