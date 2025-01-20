<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ConfigEmployeeComm;
use App\Models\ConfigEmployeeCommItem;
use App\Models\ConfigIncentive;
use App\Models\EmployeeIncentive;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    //----------------------------------------------------------------------------
    //                  Sales & Commissions
    //----------------------------------------------------------------------------
    /**
     * Get the waiter's sales & commissions data for dashboard chart display
     */
    public function getSalesCommissionSummary()
    {
        $user = User::findOrFail(2); // Should get auth user

        // Sales
        $salesEachMonth = Payment::query()
                                    ->join('orders', function ($join) {
                                        $join->on('payments.order_id', '=', 'orders.id')
                                                ->where('orders.status', 'Order Completed');
                                    })
                                    ->join('order_items', function ($join) use ($user) {
                                        $join->on('orders.id', '=', 'order_items.order_id')
                                                ->where('order_items.user_id', $user->id)
                                                ->where('order_items.status', 'Served');
                                    })
                                    ->selectRaw('MONTH(orders.created_at) as month, SUM(order_items.amount) as total_sales')
                                    ->where('payments.status', 'Successful')
                                    ->whereYear('orders.created_at', now()->year)
                                    ->whereHas('order.orderItems', fn ($query) => $query->where('user_id', $user->id))
                                    ->groupBy('month')
                                    ->orderBy('month')
                                    ->pluck('total_sales', 'month');

        // Commissions: Pre-aggregate commissions data by month
        $commissionsEachMonth = OrderItem::query()
                                            ->join('orders', function ($join) {
                                                $join->on('order_items.order_id', '=', 'orders.id')
                                                        ->where('orders.status', 'Order Completed');
                                            })
                                            ->join('payments', function ($join) {
                                                $join->on('orders.id', '=', 'payments.order_id')
                                                        ->where('payments.status', 'Successful');
                                            })
                                            ->join('products', 'order_items.product_id', '=', 'products.id')
                                            ->join('config_employee_comm_items as comm_items', function ($join) {
                                                $join->on('products.id', '=', 'comm_items.item')
                                                        ->whereColumn('comm_items.created_at', '<=', 'order_items.created_at')
                                                        ->whereNull('comm_items.deleted_at');
                                            })
                                            ->join('config_employee_comms as comms', function ($join) {
                                                $join->whereColumn('comm_items.comm_id', '=', 'comms.id')
                                                        ->whereNull('comms.deleted_at');
                                            })
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
                                            ->where([
                                                ['order_items.user_id', $user->id],
                                                ['order_items.status', 'Served'],
                                                ['order_items.type', 'Normal']
                                            ])
                                            ->whereYear('orders.created_at', now()->year)
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
            // $salesData = $salesEachMonth->firstWhere('month', $month);
    
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
    }

    /**
     * Get the waiter's sales histories 
     */
    public function getSalesHistories()
    {
    }

    /**
     * Get the waiter's sale's details 
     */
    public function getSalesDetails()
    {
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
