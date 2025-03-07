<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    //

    public function transactionListing()
    {

        return Inertia::render('TransactionListing/TransactionListing');
    }

    public function getSalesTransaction(Request $request)
    {

        $transactions = Payment::query()
            ->whereNot('status', 'pending')
            ->with(['customer']);

        if ($request->dateFilter) {
            $startDate = Carbon::parse($request->dateFilter[0])->toDateTimeString();
            $endDate = Carbon::parse($request->dateFilter[1])->toDateTimeString();
    
            $transactions->whereBetween('receipt_end_date', [$startDate, $endDate]);
        }

        $transactions = $transactions->get();

        return response()->json($transactions);
    }
}
