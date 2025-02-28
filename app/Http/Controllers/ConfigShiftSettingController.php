<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftBreak;
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

        $shifts = Shift::get();

        return response()->json($shifts);
    }

    public function createShift(Request $request)
    {
        dd($request->all());
        $validated = $request->validate([
            'shift_name' => ['required'],
            'shift_code' => ['required'],
            'time_start' => ['required'],
            'time_end' => ['required'],
            'late' => ['required'],
            'color' => ['required'],
            'break' => ['required', 'array', 'min:1'],
            'days' => ['required', 'array', 'min:1'],
        ]);

        $shift = Shift::create([
            'shift_name' => $request->shift_name,
            'shift_code' => $request->shift_code,
            'time_start' => Carbon::parse($request->time_start)->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
            'time_end' => Carbon::parse($request->time_end)->setTimezone('Asia/Kuala_Lumpur')->format('H:i'),
            'late' => $request->late,
            'color' => $request->color,
            'days' => $request->days,
        ]);

        foreach ($request->break as $break) {

            $shiftBreak = ShiftBreak::create([
                'shift_id' => $shift->id,
                'break_value' => $break->break_value,
                'break_time' => $break->break_time,
            ]);
        }
        

        return redirect()->back();
    }
}
