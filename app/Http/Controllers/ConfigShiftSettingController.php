<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftBreak;
use App\Models\User;
use App\Models\WaiterShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfigShiftSettingController extends Controller
{
    //

    public function shiftSetting()
    {


        return Inertia::render('Configuration/MainConfiguration');
    }

    public function getShift()
    {

        $shifts = Shift::with(['shift_breaks'])->get();

        return response()->json($shifts);
    }

    public function getWaiterShift(Request $request)
    {

        if ($request->waiter_id) {

            $waiterShifts = WaiterShift::query()
                    ->where('waiter_id', $request->waiter_id)
                    ->where('weeks', $request->weeks)
                    ->get();

        } else {
            $waiterShifts = WaiterShift::get();
        }

        

        return response()->json($waiterShifts);
    }

    public function getWaiter()
    {

        $waiters = User::where('position', 'waiter')->get();

        $waiters->each(function ($waiter) {
            $waiter->image = $waiter->getFirstMediaUrl('user');
        });

        return response()->json($waiters);
    }

    public function viewShift(Request $request)
    {
        
        $waiters = WaiterShift::query()
                    ->where('waiter_id', $request->waiter_id)
                    ->where('weeks', $request->weeks)
                    ->with(['users:id,name,full_name', 'shifts'])
                    ->get();

        $findWaiter = User::find($request->waiter_id);

        $findWaiter->profile_photo_url = $findWaiter->getFirstMediaUrl('user');
        
        return response()->json([
            'waiters' => $waiters,
            'findWaiter' => $findWaiter,
        ]);
    }

    public function getFilterShift(Request $request)
    {
        

        $formattedDate = Carbon::createFromFormat('d M Y', $request->date)->format('Y-m-d');

        $waiters = WaiterShift::query()
                ->where('shift_id', $request->shift_id)
                ->whereDate('date', $formattedDate)
                ->with(['users:id,name,full_name'])
                ->get();

        $waiters->each(function ($waiter) {
            if ($waiter->users) {
                $waiter->users->profile_photo_url = $waiter->users->getFirstMediaUrl('user'); 
            }
        });

        return response()->json($waiters);
    }

    public function createShift(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'shift_name' => ['required'],
            'shift_code' => ['required'],
            'time_start' => ['required'],
            'time_end' => ['required'],
            'late' => ['required'],
            'color' => ['required'],
            'breaks' => ['required', 'array', 'min:1'],
            'days' => ['required', 'array', 'min:1'],
        ]);

        $shift = Shift::create([
            'shift_name' => $request->shift_name,
            'shift_code' => $request->shift_code,
            'shift_start' => Carbon::parse($request->time_start)->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
            'shift_end' => Carbon::parse($request->time_end)->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
            'late' => $request->late,
            'color' => $request->color,
            'apply_days' => $request->days,
        ]);

        foreach ($request->breaks as $break) {

            $shiftBreak = ShiftBreak::create([
                'shift_id' => $shift->id,
                'break_value' => $break['break_value'],
                'break_time' => $break['break_time'],
            ]);
        }
        

        return redirect()->back();
    }

    public function editShift(Request $request)
    {
        
        $validated = $request->validate([
            'shift_name' => ['required'],
            'shift_code' => ['required'],
            'time_start' => ['required'],
            'time_end' => ['required'],
            'late' => ['required'],
            'color' => ['required'],
            'breaks' => ['required', 'array', 'min:1'],
            'days' => ['required', 'array', 'min:1'],
        ]);

        $shift = Shift::find($request->shift_id);

        $shift->update([
            'shift_name' => $request->shift_name,
            'shift_code' => $request->shift_code,
            'shift_start' => Carbon::parse($request->time_start)->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
            'shift_end' => Carbon::parse($request->time_end)->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
            'late' => $request->late,
            'color' => $request->color,
            'apply_days' => $request->days,
        ]);

        // Get existing breaks for this shift
        $existingBreaks = ShiftBreak::where('shift_id', $shift->id)->get()->keyBy('id');

        // Collect incoming break IDs
        $incomingBreakIds = collect($request->breaks)->pluck('id')->filter()->toArray();

        // Delete breaks that were removed by the user
        ShiftBreak::where('shift_id', $shift->id)
            ->whereNotIn('id', $incomingBreakIds)
            ->delete();

        // Process each break in the request
        foreach ($request->breaks as $break) {

            if (isset($break['id']) && $break['id']) {
                // If the break exists, update it
                if ($existingBreaks->has($break['id'])) {
                    $existingBreaks[$break['id']]->update([
                        'break_value' => $break['break_value'],
                        'break_time' => $break['break_time'],
                    ]);
                }
            } else {
                ShiftBreak::create([
                    'shift_id' => $shift->id,
                    'break_value' => $break['break_value'],
                    'break_time' => $break['break_time'],
                ]);
            }
        }

        return redirect()->back();
    }

    public function assignShift(Request $request)
    {

        // dd($request->all());

        $validatedData = $request->validate([
            'waiter_id' => ['required', 'exists:users,id'], // Ensure waiter exists
            'assign_shift' => ['required', 'array'], // Ensure assign_shift is an array
            'assign_shift.*' => ['required', 'integer', 'exists:shifts,id'], // Ensure shift IDs are valid
            'days' => ['required', 'array', 'min:1'], // Ensure at least one day is selected
        ]);

        $waiter_id = $validatedData['waiter_id'];

        $allDays = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
        $now = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek();

        $nextMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek();
        $nextSunday = $nextMonday->copy()->endOfWeek(Carbon::SUNDAY);
        $weekRange = $nextMonday->format('Y-m-d') . ' to ' . $nextSunday->format('Y-m-d');

        $week = Carbon::now()->addWeek()->week;

        foreach ($allDays as $index => $day) {
            $shift_id = $validatedData['assign_shift'][$day] ?? 'off'; // If not selected, set as "off"
            $dayDate = $now->copy()->addDays($index)->toDateString(); // Calculate date for each weekday
    
            WaiterShift::create([
                'waiter_id' => $waiter_id,
                'shift_id' => $shift_id,
                'week_range' => $weekRange, // Week range
                'weeks' => $week, // Week 
                'days' => $day,
                'date' => $dayDate, // Assign correct date for each day
            ]);
        }

        return redirect()->back();
    }

    public function deleteShift(Request $request)
    {

        $shifts = WaiterShift::where('waiter_id', $request->waiter_id)->where('weeks', $request->weeks)->get();

        foreach($shifts as $shift) {
            $shift->delete();
        }

        return redirect()->back();
    }

    public function updateShift(Request $request)
    {

        // dd($request->all());

        foreach ($request->assign_shift as $waitershift_id => $shift_id) {
            // Ensure shift_id is stored as an integer
            $shift_id = (int) $shift_id;
    
            // Update the existing WaiterShift record
            WaiterShift::where('id', $waitershift_id)
                ->where('waiter_id', $request->waiter_id)
                ->where('weeks', $request->weeks)
                ->update([
                    'shift_id' => $shift_id
                ]);
        }

        return redirect()->back();
    }
}
