<?php

namespace App\Services;

use App\Models\RunningNumber;
use App\Models\ShiftTransaction;
use Illuminate\Support\Str;

class ShiftTransactionService
{
    public static function updateDetails(string $id)
    {
        $currentShift = ShiftTransaction::find($id);

        $grossSales = $currentShift->cash_sales + $currentShift->card_sales + $currentShift->ewallet_sales;
        $deductableAmount = $currentShift->sst_amount + $currentShift->service_tax_amount + $currentShift->total_refund + $currentShift->total_void + $currentShift->total_discount;
        $netSales = $grossSales - $deductableAmount;

        $expectedCash = $currentShift->starting_cash + $currentShift->paid_in - $currentShift->paid_out - $currentShift->cash_refund;
        $cashDifference = $currentShift->closing_cash - $expectedCash;

        $currentShift->update([
            'net_sales' => $netSales,
            'expected_cash' => $expectedCash,
            'difference' => $currentShift->closing_cash ? $cashDifference : null,
        ]);
    }
}
