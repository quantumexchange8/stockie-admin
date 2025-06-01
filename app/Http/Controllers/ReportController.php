<?php

namespace App\Http\Controllers;

use App\Models\Iventory;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PaymentRefund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    //

    public function index()
    {
        return Inertia::render('AllReport/AllReport');
    }

    /**
     * Filter report with date filter.
     */
    public function getReport(Request $request) 
    {
        $dateFilter = array_map(
            fn($date) => Carbon::parse($date)
                ->timezone('Asia/Kuala_Lumpur')
                ->toDateString(),
            $request->input('date_filter')
        );
        $typeFilter = $request->input('typeFilter');

        $startDate = Carbon::parse($dateFilter[0])->startOfDay();
        $endDate = Carbon::parse($dateFilter[1] ?? $dateFilter[0])->endOfDay();

        if ($typeFilter === 'sales_summary') {
            $data = Payment::where(function ($query) use ($dateFilter, $startDate, $endDate) {
                                $query->whereDate('receipt_start_date', '>=', $startDate)
                                        ->whereDate('receipt_start_date', '<=', $endDate);
                            })
                            ->where('status', 'Successful')
                            ->whereNotNull('transaction_id')
                            ->with([
                                'order.orderItems', 
                            ])
                            ->orderBy('id')
                            ->get();
                            
        } elseif ($typeFilter === 'payment_method') {
            $data = Payment::where(function ($query) use ($dateFilter, $startDate, $endDate) {
                                $query->whereDate('receipt_start_date', '>=', $startDate)
                                        ->whereDate('receipt_start_date', '<=', $endDate);
                            })
                            ->where('status', 'Successful')
                            ->whereNotNull('transaction_id')
                            ->with([
                                'order.orderItems', 
                                'paymentMethods',
                                'paymentRefunds',
                            ])
                            ->orderBy('id')
                            ->get();

        } elseif ($typeFilter === 'product_sales') {
            $data = OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
                                $query->whereHas('payment', function ($subquery) use ($startDate, $endDate) {
                                    $subquery->whereDate('receipt_start_date', '>=', $startDate)
                                            ->whereDate('receipt_start_date', '<=', $endDate);
                                });
                            })
                            ->where('status', 'Served')
                            ->with([
                                'order', 
                                'product', 
                            ])
                            ->orderBy('id')
                            ->get();

        } elseif ($typeFilter === 'category_sales') {
            $data = OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
                                $query->whereHas('payment', function ($subquery) use ($startDate, $endDate) {
                                    $subquery->whereDate('receipt_start_date', '>=', $startDate)
                                            ->whereDate('receipt_start_date', '<=', $endDate)
                                            ->whereNotNull('transaction_id');
                                });
                            })
                            ->where('status', 'Served')
                            ->with([
                                'order', 
                                'product', 
                            ])
                            ->orderBy('id')
                            ->get();

        } elseif ($typeFilter === 'employee_earning') {
            $data = User::where('position', 'waiter')
                        ->with([
                            'incentives', 
                        ])
                        ->orderBy('id')
                        ->get()
                        ->map(function ($user) use ($startDate, $endDate) {
                            $user['sales'] = $user->sales()
                                                ->with(['order','product.commItem.configComms'])
                                                ->whereDate('orders.created_at', '>=', $startDate)
                                                ->whereDate('orders.created_at', '<=', $endDate)
                                                ->select('order_items.*')
                                                ->orderByDesc('orders.created_at')
                                                ->get();

                            $user['commission'] = $user['sales']->reduce(function ($total, $order) {
                                                                    $product = $order->product;
                                                                    $commItem = $product->commItem;
                                                                    
                                                                    $commissionAmt = $commItem 
                                                                        ? ($commItem->configComms->comm_type === 'Fixed amount per sold product'
                                                                            ? $commItem->configComms->rate * $order->item_qty
                                                                            : $product->price * $order->item_qty * ($commItem->configComms->rate / 100))
                                                                        : 0;
                                                            
                                                                    return $total + round($commissionAmt, 2);
                                                                }, 0);

                            return $user;
                        });

        } elseif ($typeFilter === 'member_purchase') {
            $data = Payment::where(function ($query) use ($dateFilter, $startDate, $endDate) {
                                $query->whereDate('receipt_start_date', '>=', $startDate)
                                        ->whereDate('receipt_start_date', '<=', $endDate);
                            })
                            ->where('status', 'Successful')
                            ->whereNotNull('transaction_id')
                            ->with([
                                'order', 
                                'customer', 
                            ])
                            ->orderBy('id')
                            ->get();

        } elseif ($typeFilter === 'current_stock') {
            $data = Iventory::where('status', 'Active')
                            ->with([
                                'inventoryItems.itemCategory', 
                            ])
                            ->orderBy('id')
                            ->get();

        }

        return response()->json($data);
    }
}
