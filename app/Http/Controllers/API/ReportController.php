<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ConfigEmployeeComm;
use App\Models\ConfigEmployeeCommItem;
use App\Models\ConfigIncentive;
use App\Models\EmployeeIncentive;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    protected $currentYear;
    protected $authUser;
    protected $sales;
    protected $salesCommissions;
    
    public function __construct()
    {
        $this->currentYear = now()->year;

        $this->authUser = User::find(Auth::id());

        // Get all item sales of the auth user
        $this->sales = OrderItem::itemSales()->where('order_items.user_id', $this->authUser->id);

        $this->salesCommissions = $this->sales
                                        ->clone()
                                        ->join('products', 'order_items.product_id', '=', 'products.id')
                                        ->join('employee_commissions as emp_comms', 'order_items.id', '=', 'emp_comms.order_item_id')
                                        ->join('config_employee_comm_items as comm_items', 'emp_comms.comm_item_id', '=', 'comm_items.id')
                                        ->join('config_employee_comms as comms', 'comm_items.comm_id', '=', 'comms.id')
                                        ->whereColumn('comm_items.created_at', '<=', 'order_items.created_at')
                                        ->whereNull('comm_items.deleted_at')
                                        ->whereNull('comms.deleted_at');
    }

    private function formatDateFilter($dateFilter)
    {
        return $dateFilter 
                ? collect($dateFilter)
                        ->map(fn($date) => Carbon::parse($date)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'))
                        ->toArray() 
                : [];
    }

    //----------------------------------------------------------------------------
    //                  Sales & Commissions
    //----------------------------------------------------------------------------
    /**
     * Get the waiter's sales & commissions data for dashboard chart display
     */
    public function getSalesCommissionSummary()
    {
        // Sales
        $salesEachMonth = $this->sales->clone()
                                        ->whereYear('orders.created_at', $this->currentYear)
                                        ->selectRaw('MONTH(orders.created_at) as month, SUM(order_items.amount) as total_sales')
                                        ->groupBy('month')
                                        ->orderBy('month')
                                        ->pluck('total_sales', 'month');

        // Commissions: Pre-aggregate commissions data by month
        $commissionsEachMonth = $this->salesCommissions->clone()
                                                        ->whereYear('orders.created_at', $this->currentYear)
                                                        ->selectRaw("
                                                            MONTH(orders.created_at) as month,
                                                            SUM(
                                                                CASE
                                                                    WHEN comms.comm_type = 'Fixed amount per sold product'
                                                                    THEN comms.rate * order_items.item_qty
                                                                    ELSE products.price * order_items.item_qty * (comms.rate / 100)
                                                                END
                                                            ) as total_commission
                                                        ")
                                                        ->groupBy('month')
                                                        ->pluck('total_commission', 'month');
                                
        $salesCommissionsArray = collect(range(1,  12))->map(function ($month) use ($salesEachMonth, $commissionsEachMonth) {
            return [
                'month' => Carbon::createFromDate(null, $month, 1)->format('F'),
                'total_sales' => number_format($salesEachMonth->get($month, 0), 2),
                'total_commissions' => number_format($commissionsEachMonth->get($month, 0), 2),
            ];
        });

        return response()->json($salesCommissionsArray);
    }

    /**
     * Get the waiter's sales & commissions details & chart data
     */
    public function getSalesCommissionDetails(Request $request)
    {
        $now = Carbon::now(); 
        $timeframe = $request->timeframe;

        $periodLabels = collect(); 
        $salesData = collect(); 
        $commissionsData = collect();   
        
        switch ($timeframe) { 
            case 'week': 
                $startOfWeek = $now->clone()->startOfWeek(Carbon::SUNDAY);
                $endOfWeek = $now->clone()->endOfWeek(Carbon::SATURDAY); 
                $dateRange = [$startOfWeek, $endOfWeek]; // Labels for the week 
                $periodLabels = collect(range(0, 6))->mapWithKeys(fn ($day) => [$day + 1 => $startOfWeek->copy()->addDays($day)->format('l')]); 
                $dateColumn = 'DAYOFWEEK'; 
                break; 

            case 'month': 
                $startOfMonth = $now->clone()->startOfMonth(); 
                $endOfMonth = $now->clone()->endOfMonth(); 
                $dateRange = [$startOfMonth, $endOfMonth]; // Labels for the month 
                $periodLabels = collect(range(1, $now->daysInMonth))->mapWithKeys(fn ($day) => [$day => $day]); $dateColumn = 'DAY'; 
                break; 
                
            case 'year': 
            default: 
                $startOfYear = $request->year ? Carbon::create($request->year)->startOfYear() : $now->clone()->startOfYear(); 
                $endOfYear = $request->year ? Carbon::create($request->year)->endOfYear() : $now->clone()->endOfYear(); 
                $dateRange = [$startOfYear, $endOfYear];

                // Labels for the year 
                $periodLabels = collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => Carbon::createFromDate($now->year, $month, 1)->format('F')]); 

                $dateColumn = 'MONTH'; 
                break; 
        }
        
        // Sales Data Query 
        $salesData = $this->sales
                            ->clone()
                            ->whereBetween('orders.created_at', $dateRange) 
                            ->selectRaw("$dateColumn(orders.created_at) as period, SUM(order_items.amount) as total_sales") 
                            ->groupBy('period') 
                            ->orderBy('period') 
                            ->get() 
                            ->mapWithKeys(fn ($item) => [$item->period => (float) $item->total_sales]); 

        // Commissions Data Query                         
        $commissionsData = $this->salesCommissions 
                                ->clone()
                                ->whereBetween('orders.created_at', $dateRange) 
                                ->selectRaw(" 
                                    $dateColumn(orders.created_at) as period, 
                                    SUM( 
                                        CASE WHEN comms.comm_type = 'Fixed amount per sold product' 
                                        THEN comms.rate * order_items.item_qty ELSE products.price * order_items.item_qty * (comms.rate / 100) 
                                        END 
                                    ) as total_commission 
                                ") 
                                ->groupBy('period') 
                                ->orderBy('period') 
                                ->get() 
                                ->mapWithKeys(fn ($item) => [$item->period => (float) $item->total_commission]); 
                
        // Structure sales and commissions data for response 
        $salesCommissionsArray = $periodLabels->map(function ($label, $period) use ($salesData, $commissionsData) { 
            return [ 
                'period' => $label, 
                'total_sales' => number_format($salesData->get($period, 0), 2), 
                'total_commissions' => number_format($commissionsData->get($period, 0), 2), 
            ]; 
        })->values(); 
        
        $data = [ 
            'current_total_sales' => number_format($salesData->sum(), 2), 
            'current_total_commissions' => number_format($commissionsData->sum(), 2), 
            'sales_commissions' => $salesCommissionsArray 
        ];
    
        return response()->json($data);
    }

    /**
     * Get the list of years since the first sales made by the waiter 
     */
    public function getSalesYearList()
    {
        $oldestSale = $this->sales->clone()
                                ->orderBy('orders.created_at')
                                ->first('orders.created_at');

        if (!$oldestSale) {
            return response()->json([now()->year]);
        }
    
        $startYear = Carbon::parse($oldestSale->created_at)->year;
        $currentYear = now()->year;
        
        $years = collect(range($startYear, $currentYear))
                    ->map(fn($year) => [
                        'value' => $year,
                        'label' => (string) $year
                    ])
                    ->values();
    
        return response()->json($years);
    }


    /**
     * Get the waiter's recent sales histories 
     */
    public function getRecentSalesHistories()
    {
        // Sales
        $salesHistories = $this->sales->clone()
                                        ->whereYear('orders.created_at', $this->currentYear)
                                        ->selectRaw('DATE(orders.created_at) as order_date, COUNT(DISTINCT orders.id) as total_orders, SUM(order_items.amount) as total_sales')
                                        ->groupBy('order_date')
                                        ->orderByDesc('order_date')
                                        ->limit(3)
                                        ->get();

        // Commissions
        $commissionsHistories = $this->salesCommissions->clone()
                                                        ->whereYear('orders.created_at', $this->currentYear)
                                                        ->selectRaw('
                                                            DATE(orders.created_at) as order_date,
                                                            SUM(
                                                                CASE
                                                                    WHEN comms.comm_type = "Fixed amount per sold product"
                                                                    THEN comms.rate * order_items.item_qty
                                                                    ELSE products.price * order_items.item_qty * (comms.rate / 100)
                                                                END
                                                            ) as total_commissions
                                                        ')
                                                        ->groupBy('order_date')
                                                        ->orderByDesc('order_date')
                                                        ->limit(3)
                                                        ->pluck('total_commissions', 'order_date');

        $saleHistories = $salesHistories->map(function ($item) use ($commissionsHistories) {
            return [
                'date' => $item->order_date,
                'total_orders' => $item->total_orders,
                'total_sales' => number_format($item->total_sales, 2),
                'total_commissions' => number_format($commissionsHistories->get($item->order_date, 0), 2)
            ];
        });

        return response()->json($saleHistories);
    }

    /**
     * Get the waiter's sales histories 
     */
    public function getSalesHistories(Request $request)
    {
        $dateFilter = $this->formatDateFilter($request->date_range);

        // Sales
        $salesHistories = $this->sales->clone()
                                        ->when(count($dateFilter) === 1, fn($query) => 
                                            $query->whereDate('orders.created_at', $dateFilter[0])
                                        )
                                        ->when(count($dateFilter) > 1, fn($query) => 
                                            $query->whereDate('orders.created_at', '>=', $dateFilter[0])
                                                    ->whereDate('orders.created_at', '<=', $dateFilter[1])
                                        )
                                        ->selectRaw('
                                            DATE(orders.created_at) as order_date, 
                                            COUNT(DISTINCT orders.id) as total_orders, 
                                            SUM(order_items.amount) as total_sales
                                        ')
                                        ->groupBy('order_date')
                                        ->orderByDesc('order_date')
                                        ->get();

        if (count($salesHistories) > 0) {
            // Commissions
            $commissionsHistories = $this->salesCommissions->clone()
                                                            ->when(count($dateFilter) === 1, fn($query) => 
                                                                $query->whereDate('orders.created_at', $dateFilter[0])
                                                            )
                                                            ->when(count($dateFilter) > 1, fn($query) => 
                                                                $query->whereDate('orders.created_at', '>=', $dateFilter[0])
                                                                        ->whereDate('orders.created_at', '<=', $dateFilter[1])
                                                            )
                                                            ->selectRaw('
                                                                DATE(orders.created_at) as order_date,
                                                                SUM(
                                                                    CASE
                                                                        WHEN comms.comm_type = "Fixed amount per sold product"
                                                                        THEN comms.rate * order_items.item_qty
                                                                        ELSE products.price * order_items.item_qty * (comms.rate / 100)
                                                                    END
                                                                ) as total_commissions
                                                            ')
                                                            ->groupBy('order_date')
                                                            ->orderByDesc('order_date')
                                                            ->pluck('total_commissions', 'order_date');
    
            $saleHistories = $salesHistories->map(function ($item) use ($commissionsHistories) {
                return [
                    'date' => $item->order_date,
                    'total_orders' => $item->total_orders,
                    'total_sales' => number_format($item->total_sales, 2),
                    'total_commissions' => number_format($commissionsHistories->get($item->order_date, 0), 2)
                ];
            });

            return response()->json($saleHistories);
        }

        return response()->json([]);
    }

    /**
     * Get the waiter's sale's details 
     */
    public function getOrderHistories()
    {
        // Sales for the specific date
        $salesDetails = $this->sales->clone()
                                    ->selectRaw('
                                        orders.id, 
                                        orders.order_no,
                                        orders.created_at,
                                        SUM(order_items.amount) as total_sales
                                    ')
                                    ->groupBy('orders.id', 'orders.order_no', 'orders.created_at')
                                    ->get();

        // Commissions for the specific date
        $commissionsDetails = $this->salesCommissions->clone()
                                                        ->selectRaw('
                                                            orders.id,
                                                            SUM(
                                                                CASE
                                                                    WHEN comms.comm_type = "Fixed amount per sold product"
                                                                    THEN comms.rate * order_items.item_qty
                                                                    ELSE products.price * order_items.item_qty * (comms.rate / 100)
                                                                END
                                                            ) as total_commissions
                                                        ')
                                                        ->groupBy('orders.id')
                                                        ->pluck('total_commissions', 'orders.id');

        $orderDetails = $salesDetails->map(function ($item) use ($commissionsDetails) {
            return [
                'order_id' => $item->id,
                'order_no' => $item->order_no,
                'order_date' => $item->created_at->format('d/m/Y, H:i'),
                'total_sales' => number_format($item->total_sales, 2),
                'total_commissions' => number_format($commissionsDetails->get($item->id, 0), 2)
            ];
        })->sortByDesc('order_date')->values();
        
        return response()->json($orderDetails);
    }

    /**
     * Get the waiter's sale's details 
     */
    public function getSalesDetails(Request $request)
    {
        $orderItemsDetails = $this->sales->clone()
                                            ->where('order_items.order_id', $request->id)
                                            ->with(['product:id,product_name', 'commission:id,order_item_id,amount'])
                                            ->select(
                                                'order_items.id',
                                                'order_items.order_id',
                                                'order_items.amount',
                                                'order_items.item_qty',
                                                'order_items.product_id',
                                                'orders.order_no',
                                                'orders.user_id',
                                                'orders.created_at'
                                            )
                                            ->get();

        // Since we know all items are from the same order, we can use the first item
        $firstItem = $orderItemsDetails->first();

        $orderDetails = [
            'id' => $firstItem->order_id,
            'order_no' => $firstItem->order_no,
            'created_at' => $firstItem->created_at,
            'waiter' => $firstItem->user_id,
            'order_items' => $orderItemsDetails->map(fn ($item) => [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'product_name' => $item->product->product_name,
                'item_qty' => $item->item_qty,
                'amount' => number_format($item->amount, 2),
                'commission' => number_format($item->commission?->amount ?? 0, 2),
            ]),
        ];

        return response()->json($orderDetails);
    }
    
    //----------------------------------------------------------------------------
    //                  Incentive
    //----------------------------------------------------------------------------
    /**
     * Get the waiter's incentive data for dashboard chart display
     */
    public function getIncentiveSummary()
    {
        $incentiveData = $this->authUser->incentives()
                                        ->whereYear('period_start', 2024)
                                        ->orderBy('period_start')
                                        ->get(['rate', 'amount', 'period_start'])
                                        ->map(fn ($incentive) => [
                                            'month' => Carbon::parse($incentive->period_start)->format('n'),
                                            'month_name' => Carbon::parse($incentive->period_start)->format('F'),
                                            'sales' => number_format($incentive->amount, 2),
                                            'incentive_rate' => number_format($incentive->rate, 2),
                                        ])
                                        ->keyBy('month');

        $incentivesArray = collect(range(1,  12))->map(function ($month) use ($incentiveData) {
            $monthData = $incentiveData->get($month, [
                'sales' => '0.00',
                'incentive_rate' => '0.00'
            ]);
            
            return [
                'month' => Carbon::createFromDate(null, $month, 1)->format('F'),
                'sales' => $monthData['sales'] ?? '0.00',
                'incentive_rate' => $monthData['incentive_rate'] ?? '0.00',
            ];
        });

        return response()->json($incentivesArray);
    }

    /**
     * Get the waiter's incentive details & chart data
     */
    public function getIncentiveDetails(Request $request)
    {
        $yearFilter = $request->year ?: $this->currentYear;

        $incentiveData = $this->authUser->incentives()
                                        ->whereYear('period_start', $yearFilter)
                                        ->orderBy('period_start')
                                        ->get(['rate', 'amount', 'period_start'])
                                        ->map(function ($incentive) {
                                            $date = Carbon::parse($incentive->period_start);
                                        
                                            return [
                                                'month' => $date->format('n'),
                                                'month_name' => $date->format('F'),
                                                'incentive_rate' => (float) $incentive->rate,
                                            ];
                                        })
                                        ->keyBy('month');

        // Pre-generate all month names to avoid multiple Carbon instances
        $monthNames = collect(range(1, 12))->mapWithKeys(function ($month) use ($yearFilter) {
            return [$month => Carbon::createFromDate($yearFilter, $month, 1)->format('F')];
        });
    
        $incentivesArray = $monthNames->map(function ($monthName, $month) use ($incentiveData) {
            $monthData = $incentiveData->get($month, ['incentive_rate' => '0.00']);
            
            return [
                'month' => $monthName,
                'incentive_rate' => number_format($monthData['incentive_rate'], 2),
            ];
        })->values();

        $data = [
            'current_total_incentive_rate' => number_format($incentiveData->sum('incentive_rate'), 2),
            'incentives' => $incentivesArray
        ];
    
        return response()->json($data);
    }

    /**
     * Get the waiter's recent incentive histories  
     */
    public function getRecentIncentiveHistories()
    {
        $incentiveData = $this->authUser->incentives()
                                        ->orderBy('period_start')
                                        ->limit(3)
                                        ->get(['rate', 'amount', 'period_start'])
                                        ->map(fn ($incentive) => [
                                            'month' => Carbon::parse($incentive->period_start)->format('M Y'),
                                            'start_date' => Carbon::parse($incentive->period_start)->format('d/m/Y'),
                                            'sales' => number_format($incentive->amount, 2),
                                            'incentive_rate' => number_format($incentive->rate, 2),
                                        ]);

        return $incentiveData;
    }

    /**
     * Get the waiter's incentive histories 
     */
    public function getIncentiveHistories(Request $request)
    {
        $dateFilter = $this->formatDateFilter($request->date_range);

        $incentiveData = $this->authUser->incentives()
                                        ->when(count($dateFilter) === 1, fn($query) => 
                                            $query->whereDate('period_start', $dateFilter[0])
                                        )
                                        ->when(count($dateFilter) > 1, fn($query) => 
                                            $query->whereDate('period_start', '>=', $dateFilter[0])
                                                    ->whereDate('period_start', '<=', $dateFilter[1])
                                        )
                                        ->orderBy('period_start')
                                        ->get(['rate', 'amount', 'period_start'])
                                        ->map(fn ($incentive) => [
                                            'month' => Carbon::parse($incentive->period_start)->format('M Y'),
                                            'start_date' => Carbon::parse($incentive->period_start)->format('d/m/Y'),
                                            'sales' => number_format($incentive->amount, 2),
                                            'incentive_rate' => number_format($incentive->rate, 2),
                                        ]);

        return count($incentiveData) > 0 ? $incentiveData : [];
    }
}
