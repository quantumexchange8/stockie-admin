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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    protected $authUser;
    protected $sales;
    protected $salesCommissions;
    
    public function __construct()
    {
        $this->authUser = User::findOrFail(2); // Should get auth user
        // Get all item sales of the auth user
        $this->sales = OrderItem::itemSales()->where('order_items.user_id', $this->authUser->id);
        $this->salesCommissions = $this->sales
                                        ->clone()
                                        ->join('products', 'order_items.product_id', '=', 'products.id')
                                        ->join('config_employee_comm_items as comm_items', 'products.id', '=', 'comm_items.item')
                                        ->join('config_employee_comms as comms', 'comm_items.comm_id', '=', 'comms.id')
                                        ->whereColumn('comm_items.created_at', '<=', 'order_items.created_at')
                                        ->whereNull('comm_items.deleted_at')
                                        ->whereNull('comms.deleted_at');
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
                                        ->whereYear('orders.created_at', now()->year)
                                        ->selectRaw('MONTH(orders.created_at) as month, SUM(order_items.amount) as total_sales')
                                        ->groupBy('month')
                                        ->orderBy('month')
                                        ->pluck('total_sales', 'month');

        // Commissions: Pre-aggregate commissions data by month
        $commissionsEachMonth = $this->salesCommissions->clone()
                                                        ->whereYear('orders.created_at', now()->year)
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

        // $commissionsEachMonthSample = OrderItem::query()
        //                                 ->join('orders', 'order_items.order_id', '=', 'orders.id')
        //                                 ->join('payments', function ($join) {
        //                                     $join->on('orders.id', '=', 'payments.order_id')
        //                                             ->where('payments.status', 'Successful');
        //                                 })
        //                                 ->join('products', 'order_items.product_id', '=', 'products.id')
        //                                 ->join('config_employee_comm_items as comm_items', function ($join) {
        //                                     $join->on('products.id', '=', 'comm_items.item')
        //                                         ->whereColumn('comm_items.created_at', '<=', 'order_items.created_at')
        //                                         ->whereNull('comm_items.deleted_at');
        //                                 })
        //                                 ->join('config_employee_comms as comms', function ($join) {
        //                                     $join->whereColumn('comm_items.comm_id', '=', 'comms.id')
        //                                             ->whereNull('comms.deleted_at');
        //                                 })
        //                                 ->selectRaw("
        //                                     MONTHNAME(order_items.created_at) as month,
        //                                     order_items.user_id,
        //                                     SUM(
        //                                         CASE
        //                                             WHEN comms.comm_type = 'Fixed amount per sold product'
        //                                             THEN comms.rate * order_items.item_qty
        //                                             ELSE products.price * order_items.item_qty * (comms.rate / 100)
        //                                         END
        //                                     ) as total_commission
        //                                 ")
        //                                 ->where('order_items.user_id', $user->id)
        //                                 ->where('order_items.status', 'Served')
        //                                 ->where('order_items.type', 'Normal')
        //                                 ->whereYear('payments.updated_at', now()->year)
        //                                 ->groupBy('month')
        //                                 ->get();
                                
        $sales_commissions_array = collect(range(1,  12))->map(function ($month) use ($salesEachMonth, $commissionsEachMonth) {
            return [
                'month' => Carbon::createFromDate(null, $month, 1)->format('F'),
                'total_sales' => number_format($salesEachMonth->get($month, 0), 2),
                'total_commissions' => number_format($commissionsEachMonth->get($month, 0), 2),
            ];
        });

        return response()->json(['sales_commissions_array' => $sales_commissions_array]);
    }

    /**
     * Get the waiter's sales & commissions details & chart data
     */
    public function getSalesCommissionDetails()
    {
        $user = User::find(2);
        $totalSales = $user->itemSales()
                            ->whereHas('order', function ($query) {
                                $query->whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year);
                            })
                            ->sum('amount');

        return response()->json($totalSales);
    }

    /**
     * Get the waiter's recent sales histories 
     */
    public function getRecentSalesHistories()
    {
        // Sales
        $salesHistories = $this->sales->clone()
                                        ->whereYear('orders.created_at', now()->year)
                                        ->selectRaw('DATE(orders.created_at) as order_date, COUNT(DISTINCT orders.id) as total_orders, SUM(order_items.amount) as total_sales')
                                        ->groupBy('order_date')
                                        ->orderByDesc('order_date')
                                        ->limit(3)
                                        ->get();

        // Commissions
        $commissionsHistories = $this->salesCommissions->clone()
                                                        ->whereYear('orders.created_at', now()->year)
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
    public function getSalesHistories()
    {
        // Sales
        $salesHistories = $this->sales->clone()
                                        ->whereYear('orders.created_at', now()->year)
                                        ->selectRaw('DATE(orders.created_at) as order_date, COUNT(DISTINCT orders.id) as total_orders, SUM(order_items.amount) as total_sales')
                                        ->groupBy('order_date')
                                        ->orderByDesc('order_date')
                                        ->limit(3)
                                        ->get();

        // Commissions
        $commissionsHistories = $this->salesCommissions->clone()
                                                        ->whereYear('orders.created_at', now()->year)
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
     * Get the waiter's sale's details 
     */
    public function getOrderHistories(Request $request)
    {
        $orderDate = Carbon::parse($request->date)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d');

        
        $salesEachMonth = $this->sales->whereYear('orders.created_at', now()->year)
                                        ->selectRaw('MONTH(orders.created_at) as month, SUM(order_items.amount) as total_sales')
                                        ->groupBy('month')
                                        ->orderBy('month')
                                        ->pluck('total_sales', 'month');
        
        $orderHistories = $this->salesCommissions
                                ->whereDate('orders.created_at', $orderDate)
                                ->select(
                                    'orders.id as order_id',
                                    'orders.order_no',
                                    'orders.created_at',
                                    DB::raw('SUM(order_items.amount) as total_amount'),
                                    DB::raw('SUM(
                                        CASE 
                                            WHEN comms.comm_type = "Fixed amount per sold product"
                                            THEN comms.rate * order_items.item_qty
                                            WHEN comms.comm_type IS NOT NULL 
                                            THEN products.price * order_items.item_qty * (comms.rate / 100)
                                            ELSE 0
                                        END
                                    ) as total_commission')
                                )
                                ->groupBy('orders.id')
                                ->get()
                                ->map(fn($order) => [
                                    'order_id' => $order->order_id,
                                    'order_no' => $order->order_no,
                                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                                    'total_amount' => number_format($order->total_amount, 2),
                                    'total_commission' => number_format($order->total_commission, 2)
                                ])
                                ->toArray();

                                
        // Sales for the specific date
        $salesDetails = $this->sales->clone()
                                    ->whereDate('orders.created_at', $orderDate)
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
                                                        ->whereDate('orders.created_at', $orderDate)
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
        });
        
        return response()->json($orderDetails);
    }

    /**
     * Get the waiter's sale's details 
     */
    public function getSalesDetails(Request $request)
    {
        $salesDetails = $this->sales->clone()->where('order_items.order_id', $request->id);
        $orderItemsDetails = $salesDetails->clone()
                                        ->with(['product:id,product_name', 'commission:id,order_item_id'])
                                        ->select(
                                            'order_items.order_id',
                                            'order_items.amount',
                                            'order_items.item_qty',
                                            'orders.order_no',
                                            'orders.user_id',
                                            'orders.created_at',
                                            'order_items.product_id'
                                        )
                                        ->get();

        $orderDetails = [
            'id' => $salesDetails->clone()->value('order_items.order_id'),
            'order_no' => $salesDetails->clone()->value('orders.order_no'),
            'created_at' => $salesDetails->clone()->value('orders.created_at'),
            'waiter' => $salesDetails->clone()->value('orders.user_id'),
            'order_items' => $orderItemsDetails->map(fn ($item) => [
                'product_name' => $item->product->product_name,
                'item_qty' => $item->item_qty,
                'amount' => number_format($item->amount, 2),
                'commission' => $item->commission, // need fix
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
    }

    /**
     * Get the waiter's incentive details & chart data
     */
    public function getIncentiveDetails()
    {
    }

    /**
     * Get the waiter's recent incentive histories  
     */
    public function getRecentIncentiveHistories()
    {
    }

    /**
     * Get the waiter's incentive histories 
     */
    public function getIncentiveHistories()
    {
    }
}
