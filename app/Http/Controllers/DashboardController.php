<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Waiter;
use App\Models\WaiterAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Log;

class DashboardController extends Controller
{
    public function index (Request $request){

        $message = $request->session()->get('message');

        //sales today
        $sales = Order::whereDate('created_at', Carbon::today())->where('status', 'Order Served')->sum('total_amount');
        if($sales == 0){
            $sales = '0';
        }

        $salesYesterday = Order::whereDate('created_at', Carbon::yesterday())->where('status','Order Served')->sum('total_amount');
        $comparedSale = 0;
        if($salesYesterday !== 0){
            $comparedSale = ($sales - $salesYesterday) / $salesYesterday*100; 
        };

        //product sold today
        $productSold = OrderItem::whereDate('created_at', Carbon::today())->where('status', 'Served')->sum('item_qty');

        $productSoldYesterday = OrderItem::whereDate('created_at', Carbon::yesterday())->where('status','Served')->sum('item_qty');
        $comparedSold = 0;
        if($productSoldYesterday !== 0){
            $comparedSold = ($productSold - $productSoldYesterday) / $productSoldYesterday*100; 
        };


        //order today
        $order = Order::whereDate('created_at', Carbon::today())->where('status', 'Order Served')->count();

        $orderYesterday = Order::whereDate('created_at', Carbon::yesterday())->where('status','Order Served')->count();
        $comparedOrder = 0;
        if($orderYesterday !== 0){
            $comparedOrder = ($order - $orderYesterday)/$orderYesterday*100; 
        };

        //table room activity

        //product low at stock
        $allProducts = IventoryItem::with(['itemCategory', 'inventory.category'])
                        ->select('inventory_id', 'item_name', 'item_cat_id', 'stock_qty')
                        ->get();

        $allProducts = $allProducts->map(function ($product) {

            $categoryName = $product->itemCategory ? $product->itemCategory->name : null;

            return [
                'inventory_id' => $product->inventory_id,
                'item_name' => $product->item_name,
                'item_cat_id' => $product->item_cat_id,
                'stock_qty' => $product->stock_qty,
                'type' => $categoryName, 
                'product_name' => $product->inventory->name,
                'low_stock_qty' => $product->itemCategory->low_stock_qty,
                'category' => $product->inventory->category->name,
            ];

            })->filter(function ($product) {
                return $product['stock_qty'] < $product['low_stock_qty'];
            });

        //on duty today
        $waiters = Waiter::all();
        $onDuty = [];
        $today = Carbon::today();

        foreach ($waiters as $waiter) {
            $attendance = WaiterAttendance::where('user_id', $waiter->id)
                ->whereDate('check_in', $today)
                ->first();
            
            $status = 'No Record';
            $time = null;
        
            if ($attendance !== null) {
                $check_in = $attendance->check_in;
                $check_out = $attendance->check_out;
        
                switch (true) {
                    case $check_in !== null && $check_out === null:
                        $status = 'Checked in';
                        $time = Carbon::parse($check_in)->format('H:i');
                        break;
                    case $check_in !== null && $check_out !== null:
                        $status = 'Checked out';
                        $time = Carbon::parse($check_out)->format('H:i');
                        break;
                }
            };
        
            $onDuty[] = [
                'id' => $waiter->id,
                'waiter_name' => $waiter->name,
                'time' => $time,
                'status' => $status,
            ];
        }
        usort($onDuty, function($a, $b) {
            $timeA = $a['time'] ? Carbon::parse($a['time']) : Carbon::parse('00:00');
            $timeB = $b['time'] ? Carbon::parse($b['time']) : Carbon::parse('00:00');
            return $timeB <=> $timeA;
        });

        //sales graph
        $salesEachMonth = Order::selectRaw('MONTHNAME(created_at) as month, 
                                                        MONTH(created_at) as month_num, 
                                                        SUM(total_amount) as total_sales')
                                ->whereYear('created_at', Carbon::now()->year)
                                ->groupBy('month', 'month_num')
                                ->orderBy('month_num')
                                ->get();
                                
        $months = $salesEachMonth->pluck('month')->map(function ($month) {
            return Carbon::parse($month)->format('M'); 
        })->toArray();

        $totalSalesArray = $salesEachMonth->pluck('total_sales')->map(function ($value) {
            return (float) $value;
        })->toArray();

        
        $products = collect($allProducts)->sortByDesc('stock_qty')->values()->all();

        return Inertia::render('Dashboard/Dashboard', [
            'message' => $message ?? [],
            'products' => $products,
            'sales' => $sales,
            'productSold' => $productSold,
            'order' => $order,
            'compareSold' => (int) round($comparedSold),
            'compareSale' => (int) round($comparedSale),
            'compareOrder' => (int) round($comparedOrder),
            'onDuty' => $onDuty,
            'salesGraph' => $totalSalesArray,
            'monthly' => $months,
        ]);
    }

    public function filterSales(Request $request)
    {
        $filterType = $request->input('activeFilter');

        $salesQuery = Order::query();

        switch ($filterType) {
            case 'month':
                $salesEachPeriod = Order::selectRaw('MONTHNAME(created_at) as month, MONTH(created_at) as month_num, YEAR(created_at) as year, SUM(total_amount) as total_sales')
                                            ->whereYear('created_at', Carbon::now()->year)                  
                                            ->groupBy('month', 'month_num', 'year')
                                            ->orderBy('year')
                                            ->orderBy('month_num')
                                            ->get();
                $labels = $salesEachPeriod->pluck('month')->map(function ($item) {
                    return Carbon::parse($item)->format('M');
                })->toArray();
                break;

            case 'year':
                $salesEachPeriod = $salesQuery->selectRaw('YEAR(created_at) as period, SUM(total_amount) as total_sales')
                                            ->groupBy('period')
                                            ->orderBy('period')
                                            ->get();

                $labels = $salesEachPeriod->pluck('period')->toArray();
                break;

            default:
                $salesEachPeriod = Order::selectRaw('MONTHNAME(created_at) as month, MONTH(created_at) as month_num, YEAR(created_at) as year, SUM(total_amount) as total_sales')
                                            ->whereYear('created_at', Carbon::now()->year)                  
                                            ->groupBy('month', 'month_num', 'year')
                                            ->orderBy('year')
                                            ->orderBy('month_num')
                                            ->get();
                $labels = $salesEachPeriod->pluck('month')->map(function ($item) {
                    return Carbon::parse($item)->format('M');
                })->toArray();
                break;
        }

        $totalSalesArray = $salesEachPeriod->pluck('total_sales')->map(function ($value) {
            return (float) $value;
        })->toArray();

        return response()->json([
            'totalSales' => $totalSalesArray,
            'labels' => $labels
        ]);
    }

}
