<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\SaleHistory;
use App\Models\Table;
use App\Models\User;
use App\Models\Waiter;
use App\Models\WaiterAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Log;

class DashboardController extends Controller
{
    public function index (Request $request){

        $message = $request->session()->get('message');

        //sales today
        $sales = Order::whereDate('created_at', Carbon::today())
                        ->where('status', 'Order Completed')
                        ->sum('total_amount');
        if($sales == 0){
            $sales = '0';
        }

        $salesYesterday = Order::whereDate('created_at', Carbon::yesterday())
                                ->where('status','Order Completed')
                                ->sum('total_amount');
        $comparedSale = 0;
        if($salesYesterday !== 0){
            $comparedSale = ($sales - $salesYesterday) / $salesYesterday*100; 
        };

        //product sold today
        $productSold = OrderItem::whereDate('created_at', Carbon::today())
                                ->where('status', 'Served')
                                ->sum('item_qty');

        $productSoldYesterday = OrderItem::whereDate('created_at', Carbon::yesterday())
                                            ->where('status','Served')
                                            ->sum('item_qty');
        $comparedSold = 0;
        if($productSoldYesterday !== 0){
            $comparedSold = ($productSold - $productSoldYesterday) / $productSoldYesterday*100; 
        };


        //order today
        $order = Order::whereDate('created_at', Carbon::today())
                        ->where('status', 'Order Completed')
                        ->count();

        $orderYesterday = Order::whereDate('created_at', Carbon::yesterday())
                                ->where('status','Order Completed')
                                ->count();
        $comparedOrder = 0;
        if($orderYesterday !== 0){
            $comparedOrder = ($order - $orderYesterday)/$orderYesterday*100; 
        };

        //table room activity
        $activeTables = Table::whereNot('status',  'Empty Seat')
                            ->select('table_no')
                            ->get();

        //product low at stock
        $allProducts = Product::with(['productItems', 'productItems.inventoryItem.itemCategory'])
                        ->where('status', '!=', 'In stock')
                        ->get();

        $allProducts = $allProducts->map(function ($product) {

            return $product->productItems->map(function ($productItem) use ($product) {
                $inventoryItem = $productItem->inventoryItem;
                $categoryName = $inventoryItem && $inventoryItem->itemCategory ? $inventoryItem->itemCategory->name : null;
        
                return [
                    'inventory_id' => $product->id,
                    'item_name' => $inventoryItem ? $inventoryItem->item_name : null,
                    'item_cat_id' => $inventoryItem ? $inventoryItem->item_cat_id : null,
                    'stock_qty' => $inventoryItem ? $inventoryItem->stock_qty : null,
                    'category' => $categoryName, 
                    'product_name' => $product->product_name,
                ];
        
            })->toArray(); 
        })->flatten(1);

        //on duty today
        $waiters = User::where('role', 'waiter')->get();
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
                'waiter_name' => $waiter->full_name,
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
        $salesEachMonth = SaleHistory::selectRaw('DATE_FORMAT(created_at, "%b") as month, 
                                                        MONTH(created_at) as month_num, 
                                                        SUM(total_price) as total_sales')
                                        ->whereYear('created_at', Carbon::now()->year)
                                        ->groupBy('month', 'month_num')
                                        ->orderBy('month_num')
                                        ->get();
                                
        $months = $salesEachMonth->pluck('month')->toArray();

        $totalSalesArray = $salesEachMonth->pluck('total_sales')
                                            ->map(function ($value) {
                                                return (float) $value;
                                            })->toArray();

        
        $products = collect($allProducts)
                    ->sortByDesc('stock_qty')
                    ->values()
                    ->all();
                    
        $todayReservations = Reservation::with([
                                                'reservedFor.reservations', 
                                                'reservedFor.reservationCancelled', 
                                                'reservedFor.reservationAbandoned', 
                                                'reservedBy', 
                                                'handledBy'
                                            ])
                                            ->whereDate('reservation_date', now('Asia/Kuala_Lumpur')->toDateString())
                                            // ->whereDate('reservation_date', '>', $yesterday)
                                            ->where(function ($query) {
                                                // Check for 'Pending' status and overdue reservation_date
                                                $query->where('status', 'Pending')
                                                    ->whereDate('reservation_date', now('Asia/Kuala_Lumpur')->toDateString());
                                
                                                // Or check for 'Delayed' status and overdue action_date
                                                $query->orWhere(function ($subQuery)  {
                                                    $subQuery->where('status', 'Delayed')
                                                        ->whereDate('action_date', now('Asia/Kuala_Lumpur')->toDateString());
                                                });
                                            })
                                            ->orderBy(DB::raw("CASE WHEN status = 'Delayed' THEN action_date ELSE reservation_date END"), 'asc')
                                            ->get();

        $waiters = User::where('role', 'waiter')
                        ->get(['id', 'full_name'])
                        ->map(function ($waiter) { 
                            return [
                                'text' => $waiter->full_name,
                                'value' => $waiter->id
                            ];
                        });

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
            'activeTables' => $activeTables,
            'reservations' => $todayReservations,
            'customers' => Customer::all(),
            'tables' => Table::orderBy('zone_id')->get(),
            'occupiedTables' => Table::where('status', '!=', 'Empty Seat')->get(),
            'waiters' => $waiters,
        ]);
    }

    public function filterSales(Request $request)
    {
        $filterType = $request->input('activeFilter');

        $salesQuery = SaleHistory::query();

        switch ($filterType) {
            case 'month':
                $salesEachPeriod = SaleHistory::selectRaw('DATE_FORMAT(created_at, "%b") as month, 
                                                        MONTH(created_at) as month_num, 
                                                        YEAR(created_at) as year, 
                                                        SUM(total_price) as total_sales')
                                                ->whereYear('created_at', Carbon::now()->year)                  
                                                ->groupBy('month', 'month_num', 'year')
                                                ->orderBy('year')
                                                ->orderBy('month_num')
                                                ->get();
                $labels = $salesEachPeriod->pluck('month')
                                            ->toArray();
                break;

            case 'year':
                $salesEachPeriod = $salesQuery->selectRaw('YEAR(created_at) as period, SUM(total_price) as total_sales')
                                                ->groupBy('period')
                                                ->orderBy('period')
                                                ->get();

                $labels = $salesEachPeriod->pluck('period')
                                            ->toArray();
                break;

            default:
                $salesEachPeriod = SaleHistory::selectRaw('MONTHNAME(created_at) as month, 
                                                        MONTH(created_at) as month_num, 
                                                        YEAR(created_at) as year, 
                                                        SUM(total_amount) as total_sales')
                                                ->whereYear('created_at', Carbon::now()->year)                  
                                                ->groupBy('month', 'month_num', 'year')
                                                ->orderBy('year')
                                                ->orderBy('month_num')
                                                ->get();
                $labels = $salesEachPeriod->pluck('month')
                                            ->map(function ($item) {
                                                return Carbon::parse($item)->format('M');
                                            })->toArray();
                break;
        }

        $totalSalesArray = $salesEachPeriod->pluck('total_sales')
                                            ->map(function ($value) {
                                                return (float) $value;
                                            })->toArray();

        return response()->json([
            'totalSales' => $totalSalesArray,
            'labels' => $labels
        ]);
    }

    public function getActiveTables ()
    {
        $activeTables = Table::whereNot('status',  'Empty Seat')
                            ->select('table_no')
                            ->get();

        return response()->json($activeTables);                            
    }

}
