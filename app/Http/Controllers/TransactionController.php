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
            ->with([
                'customer', 
                'order:id,amount,total_amount', 
                'order.orderItems:order_id,product_id,item_qty,amount_before_discount,discount_amount,amount',
                'order.orderItems.product:id,product_name,price',
                'order.orderItems.productDiscount:id,discount_id,product_name,price',
                'voucher:id,reward_type,discount'
            ]);

        if ($request->dateFilter) {
            $startDate = Carbon::parse($request->dateFilter[0])->toDateTimeString();
            $endDate = Carbon::parse($request->dateFilter[1])->toDateTimeString();
    
            $transactions->whereBetween('receipt_end_date', [$startDate, $endDate]);
        }

        $transactions = $transactions->latest()->get();

        $transactions->each(function ($transaction) {
            if ($transaction->customer) {
                $transaction->customer->profile_photo = $transaction->customer->getFirstMediaUrl('customer');
            }
        });

        return response()->json($transactions);
    }

    public function voidTransaction(Request $request)
    {

        dd($request->all());

        $transaction = Payment::find($request->id);

        $transaction->status = 'Voided';
        $transaction->save();

        return redirect()->back();
    }
}
