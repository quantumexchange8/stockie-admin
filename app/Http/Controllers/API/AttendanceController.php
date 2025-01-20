<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WaiterAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;

class AttendanceController extends Controller
{
    /**
     * Get the user's current clock in time if 
     */
    public function getCheckInTime()
    {
        $user = User::findOrFail(Auth::id()); // Should get auth user
        
        $latestCheckedIn = $user->attendances()
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
        $user = User::findOrFail(Auth::id()); // Should get auth user
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
            'check_in' => $todayAttendance?->check_in,
            'check_out' => $todayAttendance?->check_out
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
        $allAttendances = $query->where('user_id', 2) // Should get auth user
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
     * Clock in waiter 
     */
    public function checkIn(Request $request)
    {
        $this->checkRecentAttendance(User::findOrFail(Auth::id())); // Should get auth user

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

            $user = User::findOrFail(Auth::id()); // Should get auth user
            $checkInTime = Carbon::parse($validatedData['check_in']);
                    
            $existingAttendance = $this->checkExistingAttendance($user, today());
            if ($existingAttendance) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are already checked in for today.',
                ], 422);
            }

            // Case 1: No passcode exists
            if (is_null($user->passcode)) {
                return $this->handleNewPasscode($user, $checkInTime);
            }

            // Check if account is locked
            if ($user->passcode_status === 'Locked') {
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
            if ($user->passcode !== $validatedData['passcode']) {
                // RateLimiter::hit($this->throttleKey($validatedData['passcode']));
                return $this->handleFailedAttempt($user, $validatedData['passcode']);
            }

            return $this->handleSuccessfulCheckIn($user, $checkInTime);

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
     * Check for recent attendance if waiter has logged out recently within an hour then will return confirmation message
     */
    private function checkRecentAttendance(User $user)
    {
        $latestAttendance = $user->attendances()->whereBetween('check_out', [now()->subMinutes(60), now()])->first();

        if (!$latestAttendance) return;

        return response()->json([
            'status' => 'error',
            'message' => "If you meant to clock back in, confirm to get started again.",
        ], 401);
    }

    /**
     * Check for existing attendance
     */
    private function checkExistingAttendance(User $user, Carbon $checkInTime = null)
    {
        return $checkInTime 
                ? $user->attendances()
                        ->whereDate('check_in', $checkInTime->toDateString())
                        ->whereNull('check_out')
                        ->first(['id', 'check_in', 'check_out'])
                : $user->attendances()
                        ->whereNull('check_out')
                        ->latest('check_in')
                        ->first(['id', 'check_in', 'check_out']);
    }

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

            $user = User::findOrFail(Auth::id()); // Should get auth user

            // Verify existing passcode
            if ($user->passcode !== $validatedData['passcode']) {
                return $this->handleFailedAttempt($user, $validatedData['passcode']);
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

            $user = User::findOrFail(Auth::id()); // Should get auth user

            $user->update(['passcode' => $validatedData['passcode']]);
            
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
        $user = User::findOrFail(Auth::id()); // Should get auth user

        $existingAttendance = $this->checkExistingAttendance($user);
        if ($existingAttendance) {
            $checkOutAt = now();
            $existingAttendance->update(['check_out' => $checkOutAt]);

            $shiftDuration = date_diff(Carbon::parse($existingAttendance->check_in), $checkOutAt);
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
}
