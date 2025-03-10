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
    protected $sales;
    
    public function __construct()
    {
        $this->sales = Payment::query()
                                ->join('orders', 'payments.order_id', '=', 'orders.id')
                                ->where([
                                    ['payments.status', 'Successful'],
                                    ['orders.status', 'Order Completed']
                                ]);
    }

    public function index (Request $request)
    {
        $message = $request->session()->get('message');

        //sales today
        $salesToday = $this->sales->clone()
                                    ->whereDate('orders.created_at', Carbon::today())
                                    ->select('payments.grand_total')
                                    ->sum('payments.grand_total');

        $salesYesterday = $this->sales->clone()
                                        ->whereDate('orders.created_at', Carbon::yesterday())
                                        ->select('payments.grand_total')
                                        ->sum('payments.grand_total');
        $comparedSale = 0;
        if ($salesYesterday !== 0) {
            $comparedSale = ($salesToday - $salesYesterday) / $salesYesterday * 100; 
        };

        //product sold today
        $productSold = SaleHistory::soldToday();
        $productSoldYesterday = SaleHistory::soldYesterday();

        $comparedSold = 0;
        if ($productSoldYesterday !== 0) {
            $comparedSold = ($productSold - $productSoldYesterday) / $productSoldYesterday * 100; 
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

        // dd($productSold, $productSoldYesterday);
        $comparedOrder = 0;
        if ($orderYesterday !== 0) {
            $comparedOrder = ($order - $orderYesterday) / $orderYesterday * 100; 
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
        $waiters = User::where([
                            ['position', 'waiter'],
                            ['status', 'Active']
                        ])
                        ->get(['id', 'full_name'])
                        ->map(function ($waiter) { 
                            return [
                                'text' => $waiter->full_name,
                                'value' => $waiter->id,
                                'image' => $waiter->getFirstMediaUrl('user'),
                            ];
                        });

        $onDuty = [];
        $today = Carbon::today();

        foreach ($waiters as $waiter) {
            $attendance = WaiterAttendance::where('user_id', $waiter['value'])
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

            // $waiter->image = $waiter->getFirstMediaUrl('user');
        
            $onDuty[] = [
                'id' => $waiter['value'],
                'waiter_name' => $waiter['text'],
                'time' => $time,
                'status' => $status,
                'image' => $waiter['image'],
            ];
        }

        usort($onDuty, function($a, $b) {
            $timeA = $a['time'] ? Carbon::parse($a['time']) : Carbon::parse('00:00');
            $timeB = $b['time'] ? Carbon::parse($b['time']) : Carbon::parse('00:00');
            return $timeB <=> $timeA;
        });

        //sales graph

        $filteredSalesData = $this->getFilteredSalesChartData();

        $products = collect($allProducts)
                    ->sortBy('stock_qty')
                    ->take(4)
                    ->values()
                    ->all();
   
        $todayReservations = Reservation::where(function ($query) {
                                            $startDate = now()->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s');
                                            $endDate = now()->timezone('Asia/Kuala_Lumpur')->endOfDay()->format('Y-m-d H:i:s');

                                            $query->where(fn ($q) =>
                                                        $q->whereNotNull('action_date')
                                                            ->whereDate('action_date', '>=', $startDate)
                                                            ->whereDate('action_date', '<=', $endDate)
                                                    )
                                                    ->orWhere(fn ($q) =>
                                                        $q->whereNull('action_date')
                                                            ->whereDate('reservation_date', '>=', $startDate)
                                                            ->whereDate('reservation_date', '<=', $endDate)
                                                    );
                                        })
                                        ->with([
                                            'reservedFor.reservations', 
                                            'reservedFor.reservationCancelled', 
                                            'reservedFor.reservationAbandoned', 
                                            'reservedBy', 
                                            'handledBy'
                                        ])
                                        ->whereIn('status', ['Pending', 'Delayed', 'Checked in'])
                                        ->orderBy(DB::raw("CASE WHEN status = 'Delayed' THEN action_date ELSE reservation_date END"), 'asc')
                                        ->get();

        $todayReservations->each(function ($todayReservation){
            if ($todayReservation->reservedFor) {
                $todayReservation->reservedFor->image = $todayReservation->reservedFor->getFirstMediaUrl('customer');
            }
        });

        // $waiters = User::where('position', 'waiter')
        //                 ->get(['id', 'full_name'])
        //                 ->map(function ($waiter) { 
        //                     return [
        //                         'text' => $waiter->full_name,
        //                         'value' => $waiter->id,
        //                         'image' => $waiter->getFirstMediaUrl('user'),
        //                     ];
        //                 });

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
            'salesData' => $filteredSalesData['data'],
            'labels' => $filteredSalesData['labels'],
            'activeTables' => $activeTables,
            'reservations' => $todayReservations,
            'customers' => Customer::all(),
            'tables' => Table::orderBy('zone_id')->get(),
            'occupiedTables' => Table::where('status', '!=', 'Empty Seat')->get(),
            'waiters' => $waiters,
        ]);
    }

     /**
     * Filter sales graph data
     */
    public function getFilteredSalesChartData(string $timeframe = 'month')
    {
        $now = Carbon::now(); 

        $periodLabels = collect(); 
        $salesData = collect(); 
        
        switch ($timeframe) { 
            case 'week': 
                $startOfWeek = $now->clone()->startOfWeek(Carbon::SUNDAY);
                $endOfWeek = $now->clone()->endOfWeek(Carbon::SATURDAY); 
                $dateRange = [$startOfWeek, $endOfWeek]; 
                
                // Labels for the week 
                $periodLabels = collect(range(0, 6))->mapWithKeys(fn ($day) => [$day + 1 => $startOfWeek->copy()->addDays($day)->format('l')]); 
                $dateColumn = 'DAYOFWEEK'; 
                break; 

            case 'year': 
                // Changed to show previous 9 years + current year
                $startOfRange = $now->clone()->subYears(9)->startOfYear();
                $endOfRange = $now->clone()->endOfYear();
                $dateRange = [$startOfRange, $endOfRange];

                // Labels for the last 10 years
                $periodLabels = collect(range(0, 9))->mapWithKeys(function ($yearOffset) use ($now) {
                    $year = $now->year - (9 - $yearOffset);
                    return [$year => $year];
                });
                $dateColumn = 'YEAR';
                break;
                
            case 'month': 
            default: 
                $startOfYear = $now->clone()->startOfYear(); 
                $endOfYear = $now->clone()->endOfYear(); 
                $dateRange = [$startOfYear, $endOfYear];

                // Labels for all the months 
                $periodLabels = collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => Carbon::createFromDate($now->year, $month, 1)->format('M')]); 

                $dateColumn = 'MONTH'; 
                break; 
        }

        // Sales Data Query                         
        $salesData = $timeframe === 'year'
                ? $this->sales
                        ->clone()
                        ->whereBetween('orders.created_at', $dateRange)
                        ->selectRaw("
                            $dateColumn(orders.created_at) as period,
                            SUM(payments.grand_total) as total
                        ")
                        ->groupBy('period')
                        ->orderBy('period')
                        ->get()
                        ->pluck('total', 'period')
                : $this->sales
                        ->clone()
                        ->whereBetween('orders.created_at', $dateRange)
                        ->selectRaw("
                            $dateColumn(orders.created_at) as period,
                            payments.grand_total
                        ")
                        ->orderBy('period')
                        ->get()
                        ->groupBy('period')
                        ->map(function ($group) {
                            return $group->sum('grand_total');
                        });

        // Structure sales data for response 
        $salesArray = $periodLabels->map(function ($label, $period) use ($salesData) { 
            return $salesData->get($period, 0); 
        })->values(); 

        return [
            'labels' => $periodLabels->values()->toArray(),
            'data' => $salesArray->toArray()
        ];
    }


    public function filterSales(Request $request)
    {
        $filterType = $request->input('activeFilter');
        $filteredSalesData = $this->getFilteredSalesChartData($filterType);
        
        return response()->json([
            'salesData' => $filteredSalesData['data'],
            'labels' => $filteredSalesData['labels']
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
