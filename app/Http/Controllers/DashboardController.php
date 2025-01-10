<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
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
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index (Request $request){

        $message = $request->session()->get('message');

        //sales today
        // $salesToday = Order::whereDate('created_at', Carbon::today())
        //                 ->where('status', 'Order Completed')
        //                 ->sum('total_amount');
        $salesToday = Payment::whereDate('receipt_end_date', Carbon::today())
                                ->where('status', 'Successful')
                                ->sum('total_amount');

        $salesYesterday = Payment::whereDate('created_at', Carbon::yesterday())
                                ->where('status','Successful')
                                ->sum('total_amount');
        $comparedSale = 0;
        if($salesYesterday !== 0){
            $comparedSale = ($salesToday - $salesYesterday) / $salesYesterday*100; 
        };

        //product sold today
        // $productSold = OrderItem::whereDate('created_at', Carbon::today())
        //                         ->where('status', 'Served')
        //                         ->sum('item_qty');
        $productSold = SaleHistory::whereDate('created_at', Carbon::today())
                                    ->sum('qty');

        $productSoldYesterday = SaleHistory::whereDate('created_at', Carbon::yesterday())
                                            ->sum('qty');
        $comparedSold = 0;
        if($productSoldYesterday !== 0){
            $comparedSold = ($productSold - $productSoldYesterday) / $productSoldYesterday*100; 
        };


        //order today
        $order = Order::whereDate('created_at', Carbon::today())
                        ->whereHas('payment')
                        ->with(['payment' => fn($query) => $query->where('status', 'Successful')])
                        ->count();
        
        $orderYesterday = Order::whereDate('created_at', Carbon::yesterday())
                                ->where('status','Order Completed')
                                ->with(['payment' => fn($query) => $query->where('status', 'Successful')])
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
        $allProducts = Product::where('status', '!=', 'In stock')->
                                with(['productItems' => function($query) {
                                    $query->orderBy('qty');
                                }, 'productItems.inventoryItem.itemCategory'])
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
                    'image' => $product->getFirstMediaUrl('product'),
                ];
        
            })->toArray(); 
        })->flatten(1);

        //on duty today
        $waiters = User::where('position', 'waiter')->get();
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

            $waiter->image = $waiter->getFirstMediaUrl('user');
        
            $onDuty[] = [
                'id' => $waiter->id,
                'waiter_name' => $waiter->full_name,
                'time' => $time,
                'status' => $status,
                'image' => $waiter->image,
            ];
        }

        usort($onDuty, function($a, $b) {
            $timeA = $a['time'] ? Carbon::parse($a['time']) : Carbon::parse('00:00');
            $timeB = $b['time'] ? Carbon::parse($b['time']) : Carbon::parse('00:00');
            return $timeB <=> $timeA;
        });

        //sales graph
        // $salesEachMonth = SaleHistory::selectRaw('DATE_FORMAT(created_at, "%b") as month, 
        //                                                 MONTH(created_at) as month_num, 
        //                                                 SUM(total_price) as total_sales')
        //                                 ->whereYear('created_at', Carbon::now()->year)
        //                                 ->groupBy('month', 'month_num')
        //                                 ->orderBy('month_num')
        //                                 ->get();
        $salesEachMonth = Payment::where('status', 'Successful')
                                ->selectRaw('DATE_FORMAT(receipt_end_date, "%b") as month,
                                                        MONTH(receipt_end_date) as month_num,
                                                        SUM(total_amount) as total_sales')
                                ->whereYear('receipt_end_date', Carbon::now()->year)
                                ->groupBy('month', 'month_num')
                                ->orderBy('month_num')
                                ->get();
                                
        // $months = $saslesEachMonth->pluck('month')->toArray();
        $months = array_fill(0, 12, 0);
        foreach ($salesEachMonth as $sales) {
            $months[$sales->month_num - 1] = $sales->total_sales;
        }

        $totalSalesArray = $salesEachMonth->pluck('total_sales')
                                            ->map(function ($value) {
                                                return (float) $value;
                                            })->toArray();
        
        $products = collect($allProducts)
                    ->sortBy('stock_qty')
                    ->take(4)
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

        $todayReservations->each(function ($todayReservation){
            if($todayReservation->reservedFor)
            {
                $todayReservation->reservedFor->image = $todayReservation->reservedFor->getFirstMediaUrl('customer');
            }
        });

        $waiters = User::where('position', 'waiter')
                        ->get(['id', 'full_name'])
                        ->map(function ($waiter) { 
                            return [
                                'text' => $waiter->full_name,
                                'value' => $waiter->id,
                                'image' => $waiter->getFirstMediaUrl('user'),
                            ];
                        });

        return Inertia::render('Dashboard/Dashboard', [
            'message' => $message ?? [],
            'products' => $products,
            'sales' => (float)$salesToday,
            'productSold' => (int)$productSold,
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

        $salesQuery = Payment::query();

        switch ($filterType) {
            case 'month':
                // $salesEachPeriod = SaleHistory::selectRaw('DATE_FORMAT(created_at, "%b") as month, 
                //                                         MONTH(created_at) as month_num, 
                //                                         YEAR(created_at) as year, 
                //                                         SUM(total_price) as total_sales')
                //                                 ->whereYear('created_at', Carbon::now()->year)                  
                //                                 ->groupBy('month', 'month_num', 'year')
                //                                 ->orderBy('year')
                //                                 ->orderBy('month_num')
                //                                 ->get();
                $salesEachPeriod = $salesQuery->where('status', 'Successful')
                                            ->selectRaw('DATE_FORMAT(receipt_end_date, "%b") as month,
                                                                    MONTH(receipt_end_date) as month_num,
                                                                    YEAR(receipt_end_date) as year, 
                                                                    SUM(total_amount) as total_sales')
                                            ->whereYear('receipt_end_date', Carbon::now()->year)
                                            ->groupBy('month', 'month_num', 'year')
                                            ->orderBy('year')
                                            ->orderBy('month_num')
                                            ->get();
                // $labels = $salesEachPeriod->pluck('month')
                //                             ->toArray();
                $labels = array_fill(0, 12, 0);
                foreach($salesEachPeriod as $sales) {
                    $labels[$sales->month_num - 1] = $sales->total_sales;
                }
                break;

                case 'year':
                    $lastFiveYears = range(now()->year - 4, now()->year); // Array of last 5 years
                
                    $salesEachPeriod = $salesQuery->where('status', 'Successful')
                                                    ->selectRaw('YEAR(receipt_end_date) as period, SUM(total_amount) as total_sales')
                                                  ->groupBy('period')
                                                  ->orderBy('period')
                                                  ->get();
                
                    // Initialize labels with 0 for each of the last 5 years
                    $labels = array_fill(0, 5, 0);
                
                    foreach ($salesEachPeriod as $sales) {
                        $yearIndex = array_search($sales->period, $lastFiveYears); 
                        if ($yearIndex !== false) {
                            $labels[$yearIndex] = $sales->total_sales;
                        }
                    }
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
                $labels = array_fill(0, 12, 0);
                    foreach($salesEachPeriod as $sales) {
                        $labels[$sales->month_num - 1] = $sales->total_sales;
                }
                break;
        }

        // $totalSalesArray = $salesEachPeriod->pluck('total_sales')
        //                                     ->map(function ($value) {
        //                                         return (float) $value;
        //                                     })->toArray();

        return response()->json([
            'totalSales' => $salesEachPeriod,
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

    public function getActivities ()
    {
        $activityLogs = Activity::whereIn('event', ['check in', 'assign to serve', 'place to order'])
                                ->where('log_name', 'Order')
                                ->orderByDesc('created_at')
                                ->take(6)
                                ->get();

        return response()->json($activityLogs);
    }

}
