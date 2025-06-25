<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Iventory;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PaymentRefund;
use App\Models\Product;
use App\Models\Setting;
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
        $typeFilter = $request->input('typeFilter');

        $dateFilter = array_map(
            fn($date) => Carbon::parse($date)
                ->timezone('Asia/Kuala_Lumpur')
                ->toDateString(),
            $request->input('date_filter')
        );

        $setting = Setting::where([
                                    ['name', 'Cut Off Time'],
                                    ['type', 'timer'],
                                ])
                                ->first();

        [$cutoffHour, $cutoffMinute] = explode('.', (string) $setting->value);
        $cutoffMinute = str_pad((int) $cutoffMinute, 2, '0', STR_PAD_RIGHT);

        $from = Carbon::parse($dateFilter[0])->setTime($cutoffHour, $cutoffMinute);
        $to = Carbon::parse($dateFilter[1] ?? $dateFilter[0])
                    ->addDay()
                    ->setTime($cutoffHour, $cutoffMinute)
                    ->subSecond();

        $data = match($typeFilter) {
            'sales_summary' => $this->getSalesSummary($from, $to),
            'payment_method' => $this->getPaymentMethodSales($from, $to),
            'product_sales' => $this->getProductSales($from, $to),
            'category_sales' => $this->getCategorySalesData($from, $to),
            'employee_earning' => $this->getEmployeeEarningData($from, $to),
            'member_purchase' => $this->getMemberPurchaseData($from, $to),
            'current_stock' => $this->getCurrentStockData(),
        };
        
        return response()->json($data);
    }

    public function getSalesSummary($from, $to) 
    {
        $data = Payment::whereBetween('receipt_start_date', [$from, $to])
                        ->where('status', 'Successful')
                        ->whereNotNull('transaction_id')
                        ->with('order.orderItems')
                        ->orderBy('receipt_start_date')
                        ->get();

        return $data;
    }

    public function getPaymentMethodSales($from, $to) 
    {
        $data = Payment::whereBetween('receipt_start_date', [$from, $to])
                        ->where('status', 'Successful')
                        ->whereNotNull('transaction_id')
                        ->with([
                            'order.orderItems', 
                            'paymentMethods',
                            'paymentRefunds',
                        ])
                        ->orderBy('id')
                        ->get();

        return $data;
    }

    public function getProductSales($from, $to) 
    {
        $data = Product::whereHas('orderItems', fn ($query) =>
                            $query->whereHas('order', fn ($subQuery) =>
                                    $subQuery->whereHas('payment', fn ($innerQuery) =>
                                            $innerQuery->whereBetween('receipt_start_date', [$from, $to])
                                                ->where('status', 'Successful')
                                                ->whereNotNull('transaction_id')
                                        )
                                        ->where('status', 'Order Completed')
                                )
                                ->where([
                                    ['status', 'Served'],
                                    ['item_qty', '>', 0],
                                ])
                        )
                        ->orWhereHas('refundDetails', fn ($subQuery) =>
                            $subQuery->where('refund_qty', '>', 0)
                                ->whereHas('paymentRefund', fn ($innerQuery) =>
                                    $innerQuery->where('status', 'Completed')
                                            ->whereBetween('updated_at', [$from, $to])
                                )
                        )
                        ->with([
                            'orderItems' => fn ($orderItemQuery) =>
                                $orderItemQuery->where([
                                    ['status', 'Served'],
                                    ['item_qty', '>', 0],
                                ])->whereHas('order', fn ($orderQuery) =>
                                    $orderQuery->where('status', 'Order Completed')
                                            ->whereHas('payment', fn ($paymentQuery) =>
                                                $paymentQuery->whereBetween('receipt_start_date', [$from, $to])
                                                                ->where('status', 'Successful')
                                                                ->whereNotNull('transaction_id')
                                            )
                                )->with([
                                    'order.payment',
                                ]),
                            'refundDetails' => fn ($refundQuery) =>
                                $refundQuery->where('refund_qty', '>', 0)
                                    ->whereHas('paymentRefund', fn ($innerQuery) =>
                                        $innerQuery->where('status', 'Completed')
                                                ->whereBetween('updated_at', [$from, $to])
                                    ),
                        ])
                        ->orderBy('product_name')
                        ->get();

        return $data;
    }

    public function getCategorySalesData($from, $to) 
    {
        $data = Category::wherehas('products', fn ($query) =>
                            $query->whereHas('orderItems', fn ($subQuery) =>
                                    $subQuery->whereHas('order', fn ($innerQuery) =>
                                            $innerQuery->whereHas('payment', fn ($innerSubQuery) =>
                                                    $innerSubQuery->whereBetween('receipt_start_date', [$from, $to])
                                                        ->where('status', 'Successful')
                                                        ->whereNotNull('transaction_id')
                                                )
                                                ->where('status', 'Order Completed')
                                        )
                                        ->where([
                                            ['status', 'Served'],
                                            ['item_qty', '>', 0],
                                        ])
                                )
                                ->orWhereHas('refundDetails', fn ($subQuery) =>
                                    $subQuery->where('refund_qty', '>', 0)
                                        ->whereHas('paymentRefund', fn ($innerQuery) =>
                                            $innerQuery->where('status', 'Completed')
                                                    ->whereBetween('updated_at', [$from, $to])
                                        )
                                )
                        )
                        ->with([
                            'products' => fn ($productQuery) =>
                                $productQuery->whereHas('orderItems', fn ($subQuery) =>
                                    $subQuery->whereHas('order', fn ($innerQuery) =>
                                        $innerQuery->whereHas('payment', fn ($innerSubQuery) =>
                                            $innerSubQuery->whereBetween('receipt_start_date', [$from, $to])
                                                        ->where('status', 'Successful')
                                                        ->whereNotNull('transaction_id')
                                        )->where('status', 'Order Completed')
                                    )->where([
                                        ['status', 'Served'],
                                        ['item_qty', '>', 0],
                                    ])
                                )->orWhereHas('refundDetails', fn ($subQuery) =>
                                    $subQuery->where('refund_qty', '>', 0)
                                        ->whereHas('paymentRefund', fn ($innerQuery) =>
                                            $innerQuery->where('status', 'Completed')
                                                    ->whereBetween('updated_at', [$from, $to])
                                        )
                                )->with([
                                    'orderItems' => fn ($orderItemQuery) =>
                                        $orderItemQuery->where([
                                            ['status', 'Served'],
                                            ['item_qty', '>', 0],
                                        ])->whereHas('order', fn ($orderQuery) =>
                                            $orderQuery->where('status', 'Order Completed')
                                                    ->whereHas('payment', fn ($paymentQuery) =>
                                                        $paymentQuery->whereBetween('receipt_start_date', [$from, $to])
                                                                        ->where('status', 'Successful')
                                                                        ->whereNotNull('transaction_id')
                                                    )
                                        )->with([
                                            'order.payment',
                                        ]),
                                    'refundDetails' => fn ($refundQuery) =>
                                        $refundQuery->where('refund_qty', '>', 0)
                                            ->whereHas('paymentRefund', fn ($innerQuery) =>
                                                $innerQuery->where('status', 'Completed')
                                                        ->whereBetween('updated_at', [$from, $to])
                                            ),
                                ]),
                        ])
                        ->orderBy('id')
                        ->get();

        return $data;
    }

    public function getEmployeeEarningData($from, $to) 
    {
        $data = User::where('position', 'waiter')
                    ->orderBy('id')
                    ->get()
                    ->map(function ($user) use ($from, $to) {
                        $user['sales'] = $user->sales()
                                            ->with(['order','product.commItem.configComms'])
                                            ->whereBetween('orders.created_at', [$from, $to])
                                            ->select('order_items.*')
                                            ->orderByDesc('orders.created_at')
                                            ->get();

                        $user['commission'] = round($user['sales']->reduce(function ($total, $order) {
                                                                $product = $order->product;
                                                                $commItem = $product->commItem;
                                                                
                                                                $commissionAmt = $commItem 
                                                                    ? ($commItem->configComms->comm_type === 'Fixed amount per sold product'
                                                                        ? $commItem->configComms->rate * $order->item_qty
                                                                        : $product->price * $order->item_qty * ($commItem->configComms->rate / 100))
                                                                    : 0;
                                                        
                                                                return $total + round($commissionAmt, 2);
                                                            }, 0), 2);
                        
                        $user['sales'] = round($user['sales']->reduce(fn ($total, $orderItem) => $total + round($orderItem['amount'], 2), 0), 2);
                        
                        $user['incentives'] = $user->incentives()
                                                    ->whereBetween('created_at', [$from, $to])
                                                    ->get();

                        $user['incentives'] = round($user['incentives']->reduce(function ($total, $inc) {
                                                                                $amount = $inc['type'] === 'fixed'
                                                                                    ? (float)$inc['rate']
                                                                                    : ((float)$inc['rate'] / $inc['amount']) * 100;

                                                                                return $total + round($amount, 2);
                                                                            }, 0), 2);

                        return $user;
                    });

        return $data;
    }

    public function getMemberPurchaseData($from, $to) 
    {
        $data = Customer::whereHas('payments', function ($query) use ($from, $to) {
                                $query->whereBetween('receipt_start_date', [$from, $to])
                                    ->where('status', 'Successful')
                                    ->whereNotNull('transaction_id');
                        })
                        ->whereNot('status', 'void')
                        ->with([
                            'payments' => function ($query) use ($from, $to) {
                                $query->whereBetween('receipt_start_date', [$from, $to])
                                        ->where('status', 'Successful')
                                        ->whereNotNull('transaction_id');
                            },
                            'payments.order'
                        ])
                        ->orderBy('id')
                        ->get();

        return $data;
    }

    public function getCurrentStockData() 
    {
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

        return $data;
    }
}
