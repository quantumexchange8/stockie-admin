<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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
            $data = Customer::whereHas('payments', function ($query) use ($startDate, $endDate) {
                                 $query->where(function ($subQuery) use ($startDate, $endDate) {
                                            $subQuery->whereDate('receipt_start_date', '>=', $startDate)
                                                    ->whereDate('receipt_start_date', '<=', $endDate);
                                        })
                                        ->where('status', 'Successful')
                                        ->whereNotNull('transaction_id');
                            })
                            ->whereNot('status', 'void')
                            ->with([
                                'payments' => function ($query) use ($startDate, $endDate) {
                                    $query->where(function ($subQuery) use ($startDate, $endDate) {
                                                $subQuery->whereDate('receipt_start_date', '>=', $startDate)
                                                        ->whereDate('receipt_start_date', '<=', $endDate);
                                            })
                                            ->where('status', 'Successful')
                                            ->whereNotNull('transaction_id');
                                },
                                'payments.order'
                            ])
                            ->orderBy('id')
                            ->get();

        } elseif ($typeFilter === 'current_stock') {
            $data = Iventory::with([
                                    'inventoryItems' => function ($query) {
                                        $query->selectRaw('
                                                iventory_items.*, 
                                                SUM(
                                                    CASE 
                                                        WHEN keep_items.qty > 0 AND keep_items.cm = 0 THEN keep_items.qty
                                                        WHEN keep_items.qty = 0 AND keep_items.cm > 0 THEN 1
                                                        ELSE 0 
                                                    END
                                                ) as total_keep_qty
                                            ')
                                            ->leftJoin('product_items', 'iventory_items.id', '=', 'product_items.inventory_item_id')
                                            ->leftJoin('order_item_subitems', 'product_items.id', '=', 'order_item_subitems.product_item_id')
                                            ->leftJoin('keep_items', function ($join) {
                                                $join->on('order_item_subitems.id', '=', 'keep_items.order_item_subitem_id')
                                                     ->where('keep_items.status', 'Keep');
                                            })
                                            ->where('iventory_items.status', '!=', 'Inactive')
                                            ->groupBy('iventory_items.id');
                                    },
                                    'inventoryItems.itemCategory:id,name',
                                    'inventoryItems.productItems:id,inventory_item_id,product_id',
                                    'inventoryItems.productItems.product:id',
                                    'inventoryItems.productItems.orderSubitems:id,product_item_id',
                                ])
                                ->where('status', 'Active')
                                ->orderBy('id')
                                ->get()
                                ->map(function ($group) {
                                    $group->inventory_image = $group->getFirstMediaUrl('inventory');

                                    $group->inventoryItems->each(function ($item) {
                                        // Collect unique product and assign to $item->products
                                        $item->products = $item->productItems
                                                ->pluck('product')
                                                ->unique('id')
                                                ->map(fn($product) => [
                                                    'id' => $product->id,
                                                    'image' => $product->getFirstMediaUrl('product'),
                                                ])
                                                ->values();

                                        $item->total_keep_qty = $item->total_keep_qty ?? 0;
                            
                                        unset($item->productItems);
                                        
                                    });

                                    return $group;
                                });

        }

        
        return response()->json($data);
    }
}
