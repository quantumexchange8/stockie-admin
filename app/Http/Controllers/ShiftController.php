<?php

namespace App\Http\Controllers;

use App\Models\ShiftTransaction;
use App\Services\RunningNumberService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function viewShiftControl()
    {
        $shiftTransactions = ShiftTransaction::with(['openedBy:id,full_name', 'closedBy:id,full_name'])->get();

        $shiftTransactions->each(function ($shift) {
            if ($shift->openedBy) {
                $shift->openedBy->image = $shift->openedBy->getFirstMediaUrl('user');
            }

            if ($shift->closedBy) {
                $shift->closedBy->image = $shift->closedBy->getFirstMediaUrl('user');
            }
        });

        return Inertia::render('ShiftManagement/ShiftControl', [
            'shiftTransactions' => $shiftTransactions
        ]);
    }

    public function viewShiftRecord() 
    {
        return Inertia::render('ShiftManagement/ShiftReport', [

        ]);
    }

    public function openShift(Request $request) 
    {
        $request->validate(
            ['starting_cash' => 'required|decimal:0,2'], 
            ['required' => 'This field is required.']);


        ShiftTransaction::create([
            'opened_by' => auth()->user()->id,
            'shift_no' => RunningNumberService::getID('shift'),
            'starting_cash' => 0.00,
            'paid_no' => 0.00,
            'paid_out' => 0.00,
            'expected_cash' => 0.00,
            'cash_sales' => 0.00,
            'card_sales' => 0.00,
            'ewallet_sales' => 0.00,
            'gross_sales' => 0.00,
            'sst_amount' => 0.00,
            'service_tax_amount' => 0.00,
            'total_refund' => 0.00,
            'total_void' => 0.00,
            'total_discount' => 0.00,
            'net_sales' => 0.00,
            'shift_opened' => now(),
            'status' => 'opened',
        ]);

        $shiftTransactions = ShiftTransaction::with(['openedBy:id,full_name', 'closedBy:id,full_name'])->get();

        $shiftTransactions->each(function ($shift) {
            if ($shift->openedBy) {
                $shift->openedBy->image = $shift->openedBy->getFirstMediaUrl('user');
            }

            if ($shift->closedBy) {
                $shift->closedBy->image = $shift->closedBy->getFirstMediaUrl('user');
            }
        });

        return response()->json($shiftTransactions);
    }
}
