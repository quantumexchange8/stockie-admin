<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftBreak;
use App\Models\User;
use App\Models\WaiterShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        
        $validatedBreaks = [];
        $allItemErrors = [];

        foreach ($validated['breaks'] as $index => $item) {
            $rules = [
                'break_value' => 'required|integer',
                'break_time' => 'required|string',
            ];

            $requestMessages = [
                'required' => 'This field is required.',
                'string' => 'This field must be a string.',
                'integer' => 'This field must be an integer.',
            ];

            // Validate the item data
            $validator = Validator::make($item, $rules, $requestMessages);
            
            if ($validator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($validator->errors()->messages() as $field => $messages) {
                    $allItemErrors["breaks.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $validator->validated();
                if (isset($item['id'])) {
                    $validatedItem['id'] = $item['id'];
                }
                $validatedBreaks[] = $validatedItem;
            }
        }

        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) return redirect()->back()->withErrors($allItemErrors)->withInput();

        $shift = Shift::create([
            'shift_name' => $validated['shift_name'],
            'shift_code' => $validated['shift_code'],
            'shift_start' => Carbon::parse($validated['time_start'])->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
            'shift_end' => Carbon::parse($validated['time_end'])->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
            'late' => $validated['late'],
            'color' => $validated['color'],
            'apply_days' => $validated['days'],
        ]);

        foreach ($validatedBreaks as $break) {
            ShiftBreak::create([
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
        
        $validatedBreaks = [];
        $allItemErrors = [];

        foreach ($validated['breaks'] as $index => $item) {
            $rules = [
                'break_value' => 'required|integer',
                'break_time' => 'required|string',
            ];

            $requestMessages = [
                'required' => 'This field is required.',
                'string' => 'This field must be a string.',
                'integer' => 'This field must be an integer.',
            ];

            // Validate the item data
            $validator = Validator::make($item, $rules, $requestMessages);
            
            if ($validator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($validator->errors()->messages() as $field => $messages) {
                    $allItemErrors["breaks.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $validator->validated();
                if (isset($item['id'])) {
                    $validatedItem['id'] = $item['id'];
                }
                $validatedBreaks[] = $validatedItem;
            }
        }

        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) return redirect()->back()->withErrors($allItemErrors)->withInput();

        $shift = Shift::find($request->shift_id);

        $shift->update([
            'shift_name' => $validated['shift_name'],
            'shift_code' => $validated['shift_code'],
            'shift_start' => Carbon::parse($validated['time_start'])->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
            'shift_end' => Carbon::parse($validated['time_end'])->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
            'late' => $validated['late'],
            'color' => $validated['color'],
            'apply_days' => $validated['days'],
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
            'week' => ['required'],
        ]);

        $waiter_id = $validatedData['waiter_id'];
        $selectedDays = $validatedData['days'];
        $weekType = $validatedData['week']; // 'this_week' or 'next_week'

        $allDays = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

        if ($weekType === 'this_week') {
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        } else if ($weekType === 'next_week') {
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek();
        } else {
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks(2);
        }

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $weekRange = $weekStart->format('Y-m-d') . ' to ' . $weekEnd->format('Y-m-d');
        $weekNumber = $weekStart->week;

        // check is shift assist anot
        $checkshift = WaiterShift::where('waiter_id', $waiter_id)->where('week_range', $weekRange)->get();

        if (!empty($checkshift)) {
            // update existing week range data
            foreach ($allDays as $index => $dayName) {
                $dayDate = $weekStart->copy()->addDays($index)->toDateString();
                $shiftId = in_array($dayName, $selectedDays)
                    ? ($validatedData['assign_shift'][$dayName] ?? 'off')
                    : 'off';
            
                WaiterShift::updateOrCreate(
                    [
                        'waiter_id' => $waiter_id,
                        'week_range' => $weekRange,
                        'days' => $dayName,
                    ],
                    [
                        'shift_id' => $shiftId,
                        'weeks' => $weekNumber,
                        'date' => $dayDate,
                    ]
                );
            }

        } else {
            foreach ($allDays as $index => $dayName) {
                $dayDate = $weekStart->copy()->addDays($index)->toDateString();
                $shiftId = in_array($dayName, $selectedDays)
                    ? ($validatedData['assign_shift'][$dayName] ?? 'off')
                    : 'off';
        
                WaiterShift::create([
                    'waiter_id' => $waiter_id,
                    'shift_id' => $shiftId,
                    'week_range' => $weekRange,
                    'weeks' => $weekNumber,
                    'days' => $dayName,
                    'date' => $dayDate,
                ]);
            }
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
