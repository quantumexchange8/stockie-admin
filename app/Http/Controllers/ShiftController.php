<?php

namespace App\Http\Controllers;

use App\Models\ShiftPayHistory;
use App\Models\ShiftTransaction;
use App\Services\RunningNumberService;
use App\Services\ShiftTransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function viewShiftControl()
    {
        $shiftTransactions = $this->getShiftTransactions();

        return Inertia::render('ShiftManagement/ShiftControl', [
            'shiftTransactions' => $shiftTransactions
        ]);
    }

    public function viewShiftRecord() 
    {
        $shiftTransactions = ShiftTransaction::with([
                                                    'openedBy:id,full_name', 
                                                    'closedBy:id,full_name', 
                                                    'shiftPayHistories.handledBy:id,full_name',
                                                ])
                                                ->where('status', 'closed')
                                                ->orderByDesc('id')
                                                ->get();

        $shiftTransactions->each(function ($shift) {
            if ($shift->openedBy) {
                $shift->openedBy->image = $shift->openedBy->getFirstMediaUrl('user');
            }
            
            if ($shift->closedBy) {
                $shift->closedBy->image = $shift->closedBy->getFirstMediaUrl('user');
            }
            
            $shift->shiftPayHistories->each(function ($history) {
                if ($history->handledBy) {
                    $history->handledBy->image = $history->handledBy->getFirstMediaUrl('user');
                }
            });
        });

        return Inertia::render('ShiftManagement/ShiftRecord', [
            'shiftTransactions' => $shiftTransactions
        ]);
    }

    private function getShiftTransactions() 
    {
        $shiftTransactions = ShiftTransaction::with([
                                                    'openedBy:id,full_name', 
                                                    'closedBy:id,full_name', 
                                                    'shiftPayHistories.handledBy:id,full_name',
                                                ])
                                                ->orderByDesc('id')
                                                ->get();

        $shiftTransactions->each(function ($shift) {
            if ($shift->openedBy) {
                $shift->openedBy->image = $shift->openedBy->getFirstMediaUrl('user');
            }
            
            if ($shift->closedBy) {
                $shift->closedBy->image = $shift->closedBy->getFirstMediaUrl('user');
            }
            
            $shift->shiftPayHistories->each(function ($history) {
                if ($history->handledBy) {
                    $history->handledBy->image = $history->handledBy->getFirstMediaUrl('user');
                }
            });
        });

        return $shiftTransactions;
    }

    public function openShift(Request $request) 
    {
        $validatedData = $request->validate(
            ['starting_cash' => 'required|decimal:0,2'], 
            ['required' => 'This field is required.']
        );

        ShiftTransaction::create([
            'opened_by' => auth()->user()->id,
            'shift_no' => RunningNumberService::getID('shift'),
            'starting_cash' => $validatedData['starting_cash'],
            'paid_no' => 0.00,
            'paid_out' => 0.00,
            'cash_refund' => 0.00,
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

        $shiftTransactions = $this->getShiftTransactions();

        return response()->json($shiftTransactions);
    }

    public function closeShift(Request $request, string $id) 
    {
        $validatedData = $request->validate(
            ['closing_cash' => 'required|decimal:0,2'], 
            ['required' => 'This field is required.']
        );

        $currentShift = ShiftTransaction::find($id);

        $currentShift->update([
            'closed_by' => auth()->user()->id,
            'closing_cash' => $validatedData['closing_cash'],
            'shift_closed' => now(),
            'status' => 'closed',
        ]);

        ShiftTransactionService::updateDetails($id);

        $shiftTransactions = $this->getShiftTransactions();
        
        return response()->json($shiftTransactions);
    }

    public function shiftPayTransaction(Request $request, string $id) 
    {
        $validatedData = $request->validate(
            [
                'amount' => 'required|decimal:0,2',
                'reason' => 'required|string',
                'type' => 'required|string',
            ], 
            ['required' => 'This field is required.']
        );

        ShiftPayHistory::create([
            'shift_transaction_id' => $id,
            'user_id' => auth()->user()->id,
            'type' => $validatedData['type'],
            'amount' => $validatedData['amount'],
            'reason' => $validatedData['reason'],
        ]);

        $currentShift = ShiftTransaction::find($id);

        switch ($validatedData['type']) {
            case 'in':
                $currentShift->increment('paid_in', $validatedData['amount']);
                break;
                
                case 'out':
                $currentShift->increment('paid_out', $validatedData['amount']);
                break;
        }

        ShiftTransactionService::updateDetails($id);

        $shiftTransactions = $this->getShiftTransactions();

        return response()->json($shiftTransactions);
    }

    public function getFilteredShiftTransactions(Request $request) 
    {
        $dateFilter = $request->input('date_filter');

        $dateFilter = array_map(function ($date){
            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
        }, $dateFilter);

        $shiftTransactions = ShiftTransaction::where(function ($query) use ($dateFilter) {
                                                    $startDate = Carbon::parse($dateFilter[0])->startOfDay()->format('Y-m-d H:i:s');
                                                    $secondaryDate = count($dateFilter) > 1 ? $dateFilter[1] : $dateFilter[0];
                                                    $endDate = Carbon::parse($secondaryDate)->endOfDay()->format('Y-m-d H:i:s');

                                                    $query->where(fn ($q) =>
                                                                $q->whereDate('shift_opened', '>=', $startDate)
                                                                    ->whereDate('shift_opened', '<=', $endDate)
                                                            );
                                                })
                                                ->with([
                                                    'openedBy:id,full_name', 
                                                    'closedBy:id,full_name', 
                                                    'shiftPayHistories.handledBy:id,full_name',
                                                ])
                                                ->where('status', 'closed')
                                                ->orderByDesc('id')
                                                ->get();

        $shiftTransactions->each(function ($shift) {
            if ($shift->openedBy) {
                $shift->openedBy->image = $shift->openedBy->getFirstMediaUrl('user');
            }
            
            if ($shift->closedBy) {
                $shift->closedBy->image = $shift->closedBy->getFirstMediaUrl('user');
            }
            
            $shift->shiftPayHistories->each(function ($history) {
                if ($history->handledBy) {
                    $history->handledBy->image = $history->handledBy->getFirstMediaUrl('user');
                }
            });
        });
        
        return response()->json($shiftTransactions);
    }
}
