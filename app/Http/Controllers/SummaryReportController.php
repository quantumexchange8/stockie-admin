<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SaleHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class SummaryReportController extends Controller
{
    public function index(Request $request)
    {
        $message = $request->session()->get('message');

        // total sales
        $totalSales = SaleHistory::sum('total_price');
                            

        //total product sold
        $totalProducts = OrderItem::where('type', 'Normal')
                                    ->where('status', 'Served')
                                    ->sum('item_qty');

        //total orders
        $totalOrders = Order::where('status','Order Completed')->count();

        //order summary

        $ordersByMonth = array_fill(0, 12, 0);
        $orderSummary = Order::selectRaw('MONTHNAME(created_at) as month, 
                                                    MONTH(created_at) as month_num, 
                                                    SUM(CASE WHEN status = "Order Completed" THEN 1 ELSE 0 END) as total_order')
                                ->whereYear('created_at', Carbon::now()->year)
                                ->groupBy('month', 'month_num')
                                ->orderBy('month_num')
                                ->get();

        foreach ($orderSummary as $order) {
            $ordersByMonth[$order->month_num - 1] = $order->total_order;
        }

        // sales in category
        $monthlySales = array_fill(0, 12, 0);

        $orders = Order::with('orderItems.product.category')
            ->whereYear('created_at', Carbon::now()->year)
            ->get();

        foreach ($orders as $order) {
            foreach ($order->orderItems as $orderItem) {
                if ($orderItem->product->category->name === 'Beer') {
                    $monthIndex = (int) $order->created_at->format('n') - 1;
                    $monthlySales[$monthIndex] += $orderItem->amount * $orderItem->item_qty;
                }
            }
        }

        $lastPeriodSales = array_fill(0, 12, 0);

        $lastPeriodOrders = Order::with('orderItems.product.category')
            ->whereYear('created_at', Carbon::now()->subYear()->year)
            ->get();

        foreach ($lastPeriodOrders as $lastPeriodOrder) {
            foreach ($lastPeriodOrder->orderItems as $orderItem) {
                if ($orderItem->product->category->name === 'Beer') {
                    $monthIndex = (int) $lastPeriodOrder->created_at->format('n') - 1;
                    $lastPeriodSales[$monthIndex] += $orderItem->amount * $orderItem->item_qty;
                }
            }
        }

        return Inertia::render('SummaryReport/SummaryReport', [
            'message' => $message ?? [],
            'totalSales' => $totalSales,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'ordersArray' => $ordersByMonth,
            'salesCategory' => $monthlySales,
            'lastPeriodSales' => $lastPeriodSales,
        ]);
    }

    public function filterOrder(Request $request)
    {

        $filterYear = $request->input('selected');

        $ordersByMonth = array_fill(0, 12, 0);

        $orderInYear = Order::selectRaw('MONTH(created_at) as month_num, 
                                        SUM(CASE WHEN status = "Order Completed" THEN 1 ELSE 0 END) as total_order')
                            ->whereYear('created_at', $filterYear)
                            ->groupBy('month_num')
                            ->orderBy('month_num')
                            ->get();

        foreach ($orderInYear as $order) {
            $ordersByMonth[$order->month_num - 1] = $order->total_order;
        }

        return response()->json($ordersByMonth); 
    }

    public function filterSales(Request $request)
    {
        $selectedYear = $request->input('selectedYear');
        $selectedCategory = $request->input('selectedCategory');

        $thisPeriod = array_fill(0, 12, 0);
        $orders = Order::with('orderItems.product.category')
            ->whereYear('created_at', $selectedYear)
            ->get();

        foreach ($orders as $order) {
            foreach ($order->orderItems as $orderItem) {
                if ($orderItem->product->category->name === $selectedCategory) {
                    $monthIndex = (int) $order->created_at->format('n') - 1;
                    $thisPeriod[$monthIndex] += $orderItem->amount * $orderItem->item_qty;
                }
            }
        }

        $lastPeriod = array_fill(0, 12, 0);
        $orders = Order::with('orderItems.product.category')
            ->whereYear('created_at', $selectedYear-1)
            ->get();

        foreach ($orders as $order) {
            foreach ($order->orderItems as $orderItem) {
                if ($orderItem->product->category->name === $selectedCategory) {
                    $monthIndex = (int) $order->created_at->format('n') - 1;
                    $lastPeriod[$monthIndex] += $orderItem->amount * $orderItem->item_qty;
                }
            }
        }
        

        return response()->json([ 
            'thisPeriod' => $thisPeriod,
            'lastPeriod' => $lastPeriod
        ]);
    }

}
