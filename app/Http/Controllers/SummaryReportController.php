<?php

namespace App\Http\Controllers;

use App\Models\Category;
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
    public function index(Request $request)
    {
        $message = $request->session()->get('message');

        // total sales
        $totalSales = $this->sales->clone()
                                    ->select('payments.grand_total')
                                    ->sum('payments.grand_total');

        //total product sold
        $totalProducts = SaleHistory::sum('qty');

        //total orders
        $totalOrders = $this->sales->clone()->count('orders.id');

        //order summary
        $ordersByMonth = $this->getFilteredChartData('orders');

        // sales in category
        $salesByMonth = $this->getFilteredChartData('sales');

        $categories = Category::orderBy('name')
                                ->pluck('name')
                                ->toArray();

        return Inertia::render('SummaryReport/SummaryReport', [
            'message' => $message ?? [],
            'totalSales' => $totalSales,
            'totalProducts' => (int)$totalProducts,
            'totalOrders' => $totalOrders,
            'ordersArray' => $ordersByMonth,
            'categories' => $categories,
            'salesCategory' => $salesByMonth['currentYearData'],
            'lastPeriodSales' => $salesByMonth['previousYearData'],
        ]);
    }

     /**
     * Filter order summary graph data
     */
    public function getFilteredChartData(string $type, string $year = null, string $category = 'Beer')
    {
        $year ??= Carbon::now()->year;
        
        // Create date range for the specified year
        $startOfYear = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $endOfYear = Carbon::createFromDate($year, 12, 31)->endOfYear();
        
        // Get all months with their short names
        $monthLabels = collect(range(1, 12))->mapWithKeys(fn ($month) => [
            $month => Carbon::createFromDate($year, $month, 1)->format('M')
        ]);

        switch ($type) { 
            case 'orders': 
                // Orders Data Query                         
                $data = $this->sales->clone()
                                            ->whereBetween('orders.created_at', [$startOfYear, $endOfYear])
                                            ->selectRaw('MONTH(orders.created_at) as month, COUNT(DISTINCT orders.id) as order_count')
                                            ->groupBy('month')
                                            ->orderBy('month')
                                            ->get()
                                            ->pluck('order_count', 'month');
    
                // Map counts to all months, filling in zeros for months with no orders
                $monthlyData = $monthLabels
                        ->map(fn($label, $month) => $data->get($month, 0))
                        ->values();
        
                return $monthlyData->toArray();


            case 'sales': 
                // Orders Data Query                         
                $currentYearData = $this->sales->clone()
                                            ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
                                            ->join('products', 'order_items.product_id', '=', 'products.id')
                                            ->join('categories', 'products.category_id', '=', 'categories.id')
                                            ->where([
                                                ['categories.name', $category],
                                                ['order_items.status', 'Served'],
                                                ['order_items.type', 'Normal']
                                            ])
                                            ->whereBetween('orders.created_at', [$startOfYear, $endOfYear])
                                            ->selectRaw('MONTH(orders.created_at) as month, SUM(order_items.amount) as total_sales')
                                            ->groupBy('month')
                                            ->orderBy('month')
                                            ->get()
                                            ->pluck('total_sales', 'month');
        
                $previousYearData = $this->sales->clone()
                                            ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
                                            ->join('products', 'order_items.product_id', '=', 'products.id')
                                            ->join('categories', 'products.category_id', '=', 'categories.id')
                                            ->where([
                                                ['categories.name', $category],
                                                ['order_items.status', 'Served'],
                                                ['order_items.type', 'Normal']
                                            ])
                                            ->whereBetween('orders.created_at', [$startOfYear->clone()->subYear(), $endOfYear->clone()->subYear()])
                                            ->selectRaw('MONTH(orders.created_at) as month, SUM(order_items.amount) as total_sales')
                                            ->groupBy('month')
                                            ->orderBy('month')
                                            ->get()
                                            ->pluck('total_sales', 'month');
    
                // Map counts to all months, filling in zeros for months with no orders
                $currentYearMonthlyData = $monthLabels
                        ->map(fn($label, $month) => $currentYearData->get($month, 0))
                        ->values();
    
                // Map counts to all months, filling in zeros for months with no orders
                $previousYearMonthlyData = $monthLabels
                        ->map(fn($label, $month) => $previousYearData->get($month, 0))
                        ->values();
        
                return [
                    'currentYearData' => $currentYearMonthlyData->toArray(),
                    'previousYearData' => $previousYearMonthlyData->toArray()
                ];
        }
    }

    public function filterOrder(Request $request)
    {
        $filterYear = $request->input('selected');

        $ordersByMonth = $this->getFilteredChartData('orders', $filterYear);

        return response()->json($ordersByMonth); 
    }

    public function filterSales(Request $request)
    {
        $selectedYear = $request->input('selectedYear');
        $selectedCategory = $request->input('selectedCategory');

        $ordersByMonth = $this->getFilteredChartData('sales', $selectedYear, $selectedCategory);
        
        return response()->json([ 
            'thisPeriod' => $ordersByMonth['currentYearData'],
            'lastPeriod' => $ordersByMonth['previousYearData']
        ]);
    }

}
