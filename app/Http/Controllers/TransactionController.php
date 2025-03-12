<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\RefundDetail;
use App\Models\RunningNumber;
use App\Models\Setting;
use App\Services\RunningNumberService;
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
                'order.FilterOrderItems:id,order_id,product_id,item_qty,refund_qty,amount_before_discount,discount_amount,amount,discount_id',
                'order.FilterOrderItems.product:id,product_name,price',
                'order.FilterOrderItems.productDiscount:id,discount_id,price_before,price_after',
                'voucher:id,reward_type,discount',
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

    public function getRefundTransaction(Request $request)
    {

        $transactions = PaymentRefund::query()
            ->with([
                'customer', 
                'refund_details',
                'refund_details.FilterOrderItems:id,order_id,product_id,item_qty,refund_qty,amount_before_discount,discount_amount,amount',
                'refund_details.product:id,product_name,price',
                // 'order:id,amount,total_amount', 
                // 'order.FilterOrderItems:id,order_id,product_id,item_qty,refund_qty,amount_before_discount,discount_amount,amount,discount_id',
                // 'order.FilterOrderItems.product:id,product_name,price',
                // 'order.FilterOrderItems.productDiscount:id,discount_id,price_before,price_after',
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

        $transaction = Payment::find($request->id);

        $transaction->status = 'Voided';
        $transaction->save();

        return redirect()->back();
    }

    public function refundTransaction(Request $request)
    {

        dd($request->all());


        $calPoint = Setting::where('name', 'Point')->first(['point', 'value']);
        $refund_point = (int) round(($request->params['refund_subtotal'] / $calPoint->value) * $calPoint->point);

        if ($request->params['customer_id'] !== 'Guest') {
            $customer = Customer::find($request->params['customer_id']);

            $customer->point -= $refund_point;
            $customer->save();
        }

        $paymentRefund = PaymentRefund::create([
            'payment_id' => $request->params['id'],
            'customer_id' => $request->params['customer_id'],
            'refund_no' => RunningNumberService::getID('refund'),
            'subtotal_refund_amount' => $request->params['refund_subtotal'],
            'refund_sst' => $request->params['refund_sst'] ?? null,
            'refund_service_tax' => $request->params['refund_service_tax'] ?? null,
            'refund_rounding' => $request->params['refund_rounding'] ?? null,
            'total_refund_amount' => $request->params['refund_total'],
            'refund_point' => $refund_point,
            'refund_method' => $request->params['form']['refund_method'],
            'others_remark' => $request->params['form']['refund_others'] ?? null,
            'refund_remark' => $request->params['form']['refund_reason'],
            'status' => 'Completed'
        ]);

        

        foreach($request->params['form']['refund_item'] as $refund_item) {
            
            $orderItem = OrderItem::find($refund_item['id']);

            $orderItem->update([
                'refund_qty' => $refund_item['refund_quantities'],
            ]);

            $refund_detail = RefundDetail::create([
                'payment_refund_id' => $paymentRefund->id,
                'order_item_id' => $refund_item['id'],
                'refund_qty' => $refund_item['refund_quantities'],
                'refund_amount' => $refund_item['refund_amount'],
                'product_id' => $refund_item['product_id'],
            ]);
        }

        return redirect()->back();
    }

    public function voidRefundTransaction(Request $request)
    {

        $transaction = PaymentRefund::find($request->id);

        $transaction->status = 'Voided';
        $transaction->save();

        return redirect()->back();
    }
}
