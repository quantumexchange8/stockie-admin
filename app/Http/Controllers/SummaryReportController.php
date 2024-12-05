<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
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
        $totalSales = Payment::where('status', 'Successful')->sum('grand_total');

        //total product sold
        $totalProducts = SaleHistory::sum('qty');

        //total orders
        $totalOrders = Order::whereHas('payment', function ($query) {
                                $query->where('status', 'Successful');
                            })
                            ->count();


        //order summary
        $ordersByMonth = array_fill(0, 12, 0);

        $orderSummary = Order::whereHas('payment', function ($query) {
                                    $query->where('status', 'Successful');
                                })
                                ->selectRaw('MONTHNAME(updated_at) as month, 
                                            MONTH(updated_at) as month_num, 
                                            COUNT(*) as total_order')
                                ->whereYear('updated_at', Carbon::now()->year)
                                ->groupBy('month', 'month_num')
                                ->orderBy('month_num')
                                ->get();

        foreach ($orderSummary as $summary) {
            $ordersByMonth[$summary->month_num - 1] = $summary->total_order;
        }

        // sales in category
        $monthlySales = array_fill(0, 12, 0);

        $orders = Order::whereHas('payment', function ($query) {
                            $query->where('status', 'Successful');
                        })
                        ->with(['orderItems.product.category', 'payment'])
                        ->whereYear('created_at', Carbon::now()->year)
                        ->get();

        foreach ($orders as $order) {
            foreach ($order->orderItems as $orderItem) {
                if ($orderItem->product->category->name === 'Beer') {
                    $monthIndex = (int) $order->created_at->format('n') - 1;
                    $monthlySales[$monthIndex] += $orderItem->amount;
                }
            }
        }

        // $totalSalesCategory = array_reduce($monthlySales, function ($carry, $item) {
        //     return $carry + $item;
        // }, 0);
        
        // dd($totalSales, $totalSalesCategory);

        $lastPeriodSales = array_fill(0, 12, 0);

        $lastPeriodOrders = Order::whereHas('payment', function ($query) {
                                $query->where('status', 'Successful');
                            })
                        ->with('orderItems.product.category')
                        ->whereYear('created_at', Carbon::now()->subYear()->year)
                        ->get();

        // foreach ($lastPeriodOrders as $lastPeriodOrder) {
        //     foreach ($lastPeriodOrder->orderItems as $orderItem) {
        //         if ($orderItem->product->category->name === 'Beer') {
        //             $monthIndex = (int)Carbon::parse($lastPeriodOrder->receipt_end_date)->format('n')-1;
        //             $lastPeriodSales[$monthIndex] += $orderItem->amount * $orderItem->item_qty;
        //         }
        //     }
        // }
        foreach ($lastPeriodOrders as $lastPeriodOrder) {
            foreach ($lastPeriodOrder->orderItems as $orderItem) {
                if ($orderItem->product->category->name === 'Beer') {
                    $monthIndex = (int) $lastPeriodOrder->created_at->format('n') - 1;
                    $lastPeriodSales[$monthIndex] += $orderItem->amount;
                }
            }
        }

        return Inertia::render('SummaryReport/SummaryReport', [
            'message' => $message ?? [],
            'totalSales' => $totalSales,
            'totalProducts' => (int)$totalProducts,
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

        //order summary
        $orderInYear = Order::whereHas('payment', function ($query) {
                                    $query->where('status', 'Successful');
                                })
                                ->selectRaw('MONTHNAME(updated_at) as month, 
                                            MONTH(updated_at) as month_num, 
                                            COUNT(*) as total_order')
                                ->whereYear('updated_at', $filterYear)
                                ->groupBy('month', 'month_num')
                                ->orderBy('month_num')
                                ->get();

        foreach ($orderInYear as $summary) {
            $ordersByMonth[$summary->month_num - 1] = $summary->total_order;
        }

        return response()->json($ordersByMonth); 
    }

    public function filterSales(Request $request)
    {
        $selectedYear = $request->input('selectedYear');
        $selectedCategory = $request->input('selectedCategory');

        $thisPeriod = array_fill(0, 12, 0);
        $orders = Order::whereHas('payment', function ($query) {
                            $query->where('status', 'Successful');
                        })
                        ->with('orderItems.product.category')
                        ->whereYear('created_at', $selectedYear)
                        ->get();

        foreach ($orders as $order) {
            foreach ($order->orderItems as $orderItem) {
                if ($orderItem->product->category->name === $selectedCategory) {
                    $monthIndex = (int) $order->created_at->format('n') - 1;
                    $thisPeriod[$monthIndex] += $orderItem->amount;
                }
            }
        }

        $lastPeriod = array_fill(0, 12, 0);
        $lastPeriodOrders = Order::whereHas('payment', function ($query) {
                    $query->where('status', 'Successful');
                })
                ->with('orderItems.product.category')
                ->whereYear('created_at', $selectedYear-1)
                ->get();

        foreach ($lastPeriodOrders as $order) {
            foreach ($order->orderItems as $orderItem) {
                if ($orderItem->product->category->name === $selectedCategory) {
                    $monthIndex = (int) $order->created_at->format('n') - 1;
                    $lastPeriod[$monthIndex] += $orderItem->amount;
                }
            }
        }
        

        return response()->json([ 
            'thisPeriod' => $thisPeriod,
            'lastPeriod' => $lastPeriod
        ]);
    }

}
