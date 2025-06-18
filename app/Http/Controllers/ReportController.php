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

        $data = match($typeFilter) {
            'sales_summary' => $this->getSalesSummary($startDate, $endDate),
            'payment_method' => $this->getPaymentMethodSales($startDate, $endDate),
            'product_sales' => $this->getProductSales($startDate, $endDate),
            'category_sales' => $this->getCategorySalesData($startDate, $endDate),
            'employee_earning' => $this->getEmployeeEarningData($startDate, $endDate),
            'member_purchase' => $this->getMemberPurchaseData($startDate, $endDate),
            'current_stock' => $this->getCurrentStockData($startDate, $endDate),
        };
        
        return response()->json($data);
    }

    public function getSalesSummary($startDate, $endDate) 
    {
        $data = Payment::where(function ($query) use ($startDate, $endDate) {
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

        return $data;
    }

    public function getPaymentMethodSales($startDate, $endDate) 
    {
        $data = Payment::where(function ($query) use ($startDate, $endDate) {
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

        return $data;
    }

    public function getProductSales($startDate, $endDate) 
    {
        $data = Product::whereHas('orderItems', fn ($query) =>
                            $query->whereHas('order', fn ($subQuery) =>
                                    $subQuery->whereHas('payment', fn ($innerQuery) =>
                                            $innerQuery->whereDate('receipt_start_date', '>=', $startDate)
                                                ->whereDate('receipt_start_date', '<=', $endDate)
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
                                            ->whereDate('updated_at', '>=', $startDate)
                                            ->whereDate('updated_at', '<=', $endDate)
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
                                                $paymentQuery->whereDate('receipt_start_date', '>=', $startDate)
                                                                ->whereDate('receipt_start_date', '<=', $endDate)
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
                                                ->whereDate('updated_at', '>=', $startDate)
                                                ->whereDate('updated_at', '<=', $endDate)
                                    ),
                        ])
                        ->orderBy('id')
                        ->get();

        return $data;
    }

    public function getCategorySalesData($startDate, $endDate) 
    {
        $data = Category::wherehas('products', fn ($query) =>
                            $query->whereHas('orderItems', fn ($subQuery) =>
                                    $subQuery->whereHas('order', fn ($innerQuery) =>
                                            $innerQuery->whereHas('payment', fn ($innerSubQuery) =>
                                                    $innerSubQuery->whereDate('receipt_start_date', '>=', $startDate)
                                                        ->whereDate('receipt_start_date', '<=', $endDate)
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
                                                    ->whereDate('updated_at', '>=', $startDate)
                                                    ->whereDate('updated_at', '<=', $endDate)
                                        )
                                )
                        )
                        ->with([
                            'products' => fn ($productQuery) =>
                                $productQuery->whereHas('orderItems', fn ($subQuery) =>
                                    $subQuery->whereHas('order', fn ($innerQuery) =>
                                        $innerQuery->whereHas('payment', fn ($innerSubQuery) =>
                                            $innerSubQuery->whereDate('receipt_start_date', '>=', $startDate)
                                                        ->whereDate('receipt_start_date', '<=', $endDate)
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
                                                    ->whereDate('updated_at', '>=', $startDate)
                                                    ->whereDate('updated_at', '<=', $endDate)
                                        )
                                )->with([
                                    'orderItems' => fn ($orderItemQuery) =>
                                        $orderItemQuery->where([
                                            ['status', 'Served'],
                                            ['item_qty', '>', 0],
                                        ])->whereHas('order', fn ($orderQuery) =>
                                            $orderQuery->where('status', 'Order Completed')
                                                    ->whereHas('payment', fn ($paymentQuery) =>
                                                        $paymentQuery->whereDate('receipt_start_date', '>=', $startDate)
                                                                        ->whereDate('receipt_start_date', '<=', $endDate)
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
                                                        ->whereDate('updated_at', '>=', $startDate)
                                                        ->whereDate('updated_at', '<=', $endDate)
                                            ),
                                ]),
                        ])
                        ->orderBy('id')
                        ->get();

        return $data;
    }

    public function getEmployeeEarningData($startDate, $endDate) 
    {
        $data = User::where('position', 'waiter')
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
                                                    ->whereDate('created_at', '>=', $startDate)
                                                    ->whereDate('created_at', '<=', $endDate)
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

    public function getMemberPurchaseData($startDate, $endDate) 
    {
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

        return $data;
    }

    public function getCurrentStockData($startDate, $endDate) 
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
