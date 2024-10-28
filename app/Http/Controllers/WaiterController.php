<?php

namespace App\Http\Controllers;
use App\Http\Requests\WaiterRequest;
use App\Models\ConfigEmployeeComm;
use App\Models\ConfigEmployeeCommItem;
use App\Models\ConfigIncentive;
use App\Models\ConfigIncentiveEmployee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Waiter;
use App\Models\WaiterAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Validation\Rules;


class WaiterController extends Controller
{
   public function waiter(Request $request)
   {
        $waiters = User::where('role', 'waiter')->get();
        // dd($waiters);

        $message = $request->session()->get('message');

        //for sales performance graph 
        $allWaiters = User::where('role', 'waiter')->select('id', 'full_name')->get()->keyBy('id');

        $waitersSalesDetail = OrderItem::with('order.waiter')
                                        ->where('status', 'Served')
                                        ->where('type', 'Normal')
                                        ->whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->selectRaw('user_id, SUM(amount) as total_sales')
                                        ->groupBy('user_id')
                                        ->get()
                                        ->map(function ($order) {
                                            return [
                                                'waiter_id' => $order->user_id,
                                                'total_sales' => (int)$order->total_sales,
                                            ];
                                        })
                                        ->keyBy('waiter_id');
        $waitersDetail = [];
        foreach ($allWaiters as $waiter) {
            $salesDetail = $waitersSalesDetail->firstWhere('waiter_id', $waiter->id);
            $waitersDetail[] = [
                'waiter_name' => $waiter->full_name,
                'total_sales' => $salesDetail ? (int)$salesDetail['total_sales'] : 0,
            ];
        }

        // dd($allWaiters);
        $waiterIds = array_column($waitersDetail, 'waiter_name');
        $waiterSales = array_column($waitersDetail, 'total_sales');

        //for commission earned
        //step 1: get the purchased items
        $purchased = OrderItem::with('product.productItems.commItems.configComms', 'handledBy')
                                ->where('status', 'Served')
                                ->where('type', 'Normal')
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->get();
        $waitersList = User::where('role', 'waiter')->get()->keyBy('id'); 
        $result = [];

        foreach ($waitersList as $waiter) {
            $result[$waiter->id] = [
                'waiterId' => $waiter->id,
                'waiterName' => $waiter->full_name,
                'commission' => 0, 
            ];
        }
        foreach ($purchased as $order) {

            $waiterId = $order->user_id;
            $itemQty = $order->item_qty;
            $productPrice = $order->product->price;
            $commId = ConfigEmployeeCommItem::where('item', $order->product_id)
                                            ->where('created_at', '<=', $order->created_at)
                                            ->pluck('comm_id');

            $commType = ConfigEmployeeComm::whereIn('id', $commId)
                                            ->select('comm_type', 'rate')
                                            ->get()
                                            ->toArray();
            foreach ($commType as $comm) {
                $rate = $comm['rate']; 
                $commTypeValue = $comm['comm_type']; 
                if ($commTypeValue === 'Fixed amount per sold product') {
                    $commission = $rate * $itemQty;
                } else {
                    $commission = $productPrice * $rate / 100 * $itemQty;
                }

                if (isset($result[$waiterId])) {
                    $result[$waiterId]['commission'] += $commission; 
                }
            }
        }

        // dd($purchased);

        $name = array_column($result, 'waiterName');
        $commission = array_map(function($value) {
            return (float)number_format(ceil($value * 100) / 100, 2, '.', '');
        }, array_column($result, 'commission'));


        return Inertia::render('Waiter/Waiter', [
            'waiters'=> $waiters,
            'message'=> $message ?? [],
            'waiterIds' => $waiterIds,
            'waiterSales' => $waiterSales,
            'waiterNames' => $name,
            'waiterCommission' => $commission,
        ]);
    }


   public function store(WaiterRequest $request)
   {    
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
        ]);

       User::create([
            'name' => $request->username,
            'full_name' => $request->name,
            'phone' => $request->phone,
            'email'=>$request->email,
            'role_id' => $request->role_id,
            'role' => 'waiter',
            'salary'=> $request->salary,
            'worker_email' => $request->stockie_email,
            'password' => Hash::make($request->stockie_password),
       ]);

       return redirect()->route('waiter');
   }

   public function deleteWaiter (String $id)
   {
        $deleteWaiter = User::find($id);
        $deleteWaiter->delete();

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Selected waiter has been successfully deleted.'
        ];

        return Redirect::route('waiter')->with(['message' => $message]);
   }

   public function editWaiter (WaiterRequest $request)
   {
        $editWaiter = User::find($request->id);
        $editWaiter->update([
            'name' => $request->input('username'),
            'full_name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'staffid' => $request->input('staffid'),
            'salary' => $request->input('salary'),
            'worker_email' => $request->input('stockie_email'),
            'password' => $request->input('password'),
        ]);
        // dd($request->all());
        return redirect()->route('waiter');
   }

   public function showWaiterDetails(string $id)
   {    
        //waiter
        $waiterDetail = User::find($id);
                            // ->where('role', 'waiter')
                            // ->get();
        
        //attendance
        $attendance = WaiterAttendance::where('user_id', $id)
                                        ->get(['check_in', 'check_out'])
                                        ->map(function ($record) {
                                            $checkIn = Carbon::parse($record->check_in);
                                            $checkOut = Carbon::parse($record->check_out);
    
                                            $durationInSeconds = $checkIn->diffInSeconds($checkOut);
                                    
                                            $hours = floor($durationInSeconds / 3600);
                                            $minutes = floor(($durationInSeconds % 3600) / 60);
                                            $seconds = $durationInSeconds % 60;
                                    
                                            $formattedDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                    
                                            return [
                                                'check_in' => $checkIn->toDateTimeString(),
                                                'check_out' => $checkOut->toDateTimeString(),
                                                'duration' => $formattedDuration,
                                            ];
                                        });

        //existing incentive thresholds
        $configIncentive = ConfigIncentiveEmployee::where('user_id', $id)
                                                ->with('configIncentive')    
                                                ->select('incentive_id') 
                                                ->distinct() 
                                                ->get();

        //total sales of waiter this month
        $totalSales = OrderItem::where('user_id', $id)
                                ->where('status', 'Served')
                                ->where('type', 'Normal')
                                ->whereMonth('created_at', Carbon::now()->month)
                                ->sum('amount');

        // sales grouped by month
        $totalSalesMonthly = OrderItem::where('user_id', $id)
                                        ->where('status', 'Served')
                                        ->where('type', 'Normal')
                                        ->selectRaw('YEAR(created_at) as year, MONTHNAME(created_at) as month, MONTH(created_at) as month_num, SUM(amount) as total_sales')
                                        ->groupBy('year', 'month', 'month_num')
                                        ->get();
        
        //commission
        $commissionItemsIds = OrderItem::where('user_id', $id)
                                        ->where('status', 'Served')
                                        ->where('type', 'Normal')
                                        ->with('product.productItems.commItems.configComms')
                                        ->get();

        $groupCommissionItemsIds = $commissionItemsIds->groupBy(function($orderItem){
            return $orderItem->created_at->format('F Y');
        });

        $commission = $groupCommissionItemsIds->map(function ($groupItems, $monthYear) use ($totalSalesMonthly) {
            $commissionMonthNum = $groupItems->first()->created_at->month;
            $matchingSales = $totalSalesMonthly->firstWhere('month_num', $commissionMonthNum);
            $commissionAmt = 0;
            
            foreach ($groupItems as $commissionItemsId) {
                foreach ($commissionItemsId->product->productItems as $productItem) {
                    foreach ($productItem->commItems as $commItem) {
                        $commissionItemPrice = $commissionItemsId->product->price;
                        $rate = $commItem->configComms->rate;
                        $commType = $commItem->configComms->comm_type;
                        $item_qty = $commissionItemsId->item_qty;
                        
                        if ($commType === 'Fixed amount per sold product') {
                            $commissionAmt += $rate * $item_qty;
                        } else {
                            $commissionValue = $commissionItemPrice * $item_qty * ($rate / 100);
                            $commissionAmt += $commissionValue;
                        }
                    }
                }
            }
        
            return [
                'monthly_sale' => $matchingSales->total_sales ?? 0,
                'commissionAmt' => $commissionAmt,
                'created_at' => $monthYear,
            ];
        })->values()->toArray();
        
        $commissionThisMonth = collect($commission)->firstWhere('created_at', Carbon::now()->format('F Y'))['commissionAmt'] ?? 0;
        //end of commission

        //incentive
        $incentive = ConfigIncentiveEmployee::where('user_id', $id)
                                            ->with('configIncentive')
                                            ->orderBy('created_at')
                                            ->get();

        $groupIncentive = $incentive->groupBy(function($incentiveMonth) {
            return $incentiveMonth->created_at->format('F Y');
        });
        
        $incentiveData = $groupIncentive->map(function ($groupItems, $monthYear) use ($totalSalesMonthly) {
            $incentiveMonthNum = $groupItems->first()->created_at->month;

            // Find matching sales for the same month
            $matchingSales = $totalSalesMonthly->firstWhere('month_num', $incentiveMonthNum);
            $incentiveAmt = 0;
        
            // filter and keep the incentives with reached monthly sale
            $validIncentives = $groupItems->filter(function($incentive) use ($matchingSales) {
                return $matchingSales && $matchingSales->total_sales > $incentive->configIncentive->monthly_sale;
            });
            
            if ($validIncentives->isEmpty()) {
                return null;
            }
        
            // among the reached monthly sale get the highest one
            $incentive = $validIncentives->sortByDesc(function($incentive) {
                return $incentive->configIncentive->monthly_sale;
            })->first();
        
            //calculate incentive
            if ($incentive) {
                $rate = $incentive->configIncentive->rate;
                $status = $incentive->status;
                $type = $incentive->configIncentive->type;
                $incentiveId = $incentive->incentive_id;
        
                if ($type === 'fixed') {
                    $incentiveAmt = $rate;
                } else {
                    $incentiveAmt = $rate * $matchingSales->total_sales;
                }
            } else {
                $rate = 0;
                $status = 'Pending';
                $type = null;
                $incentiveId = null;
            }
        
            return [
                'incentiveId' => $incentiveId,
                'monthYear' => $monthYear,
                'type' => $type,
                'status' => $status ?? 'Pending',
                'rate' => $rate,
                'incentiveAmt' => round($incentiveAmt, 2),
                'totalSales' => $matchingSales->total_sales ?? 0,
            ];
        })->filter()->values()->toArray();
        
        $incentiveThisMonth = collect($incentiveData)->firstWhere('monthYear', Carbon::now()->format('F Y'))['incentiveAmt'] ?? 0;
        //end of incentive

        //date filter
        $dateFilter = [
            Carbon::now()->subDays(7)->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s'),
            Carbon::now()->timezone('Asia/Kuala_Lumpur')->endOfDay()->format('Y-m-d H:i:s'),
        ];

        //orders
        $allOrders = OrderItem::where('user_id', $id)
                                ->where('type', 'Normal')
                                ->where('status', 'Served')
                                ->whereBetween('created_at', $dateFilter)
                                ->orderBy('created_at','desc')
                                ->with('order', 'product.productItems.commItems.configComms')
                                ->get();

        // dd($allOrders);
        $orders = $allOrders->map(function ($order) {
            $commissionAmt = 0;
            $totalAmount = $order->amount;
            $productName = $order->product->product_name;
            $productPrice = $order->product->price;
            $item_qty = $order->item_qty;
            foreach ($order->product->productItems as $productItem) {
                foreach ($productItem->commItems as $commItem) {
                    $rate = $commItem->configComms->rate;
                    $commType = $commItem->configComms->comm_type;
    
                    if ($commType === 'Fixed amount per sold product') {
                        $commissionAmt += $rate * $order->item_qty;
                    } else {
                        $commissionValue = $productPrice * $order->item_qty * ($rate / 100);
                        $commissionAmt += $commissionValue;
                    }
                }
            }
            
            return [
                'created_at' => $order->created_at->format('d/m/Y'),
                'order_id' => $order->id,
                'order_no' => $order->order->order_no,
                'product_name' => $productName,
                'price' => $productPrice,
                'serve_qty' => $item_qty,
                'total_amount' => round($totalAmount, 2), 
                'commission' => round($commissionAmt, 2)
            ];
        });

        // dd($orders);

        $groupedOrders = $orders->groupBy('order_no')->map(function ($groupedItems, $orderNo) {
            $totalSales = $groupedItems->sum('total_amount');
            $totalCommission = $groupedItems->sum('commission');
            $createdAt = $groupedItems->first()['created_at'];
        
            return [
                'created_at' => $createdAt,
                'order_no' => $orderNo,
                'total_amount' => round($totalSales, 2),
                'commission' => round($totalCommission, 2),
                'items' => $groupedItems->toArray()
            ];
        });
        $groupedOrders = array_values($groupedOrders->toArray());
        // dd($groupedOrders);

        return Inertia::render('Waiter/Partials/WaiterDetails', [
            'id' => $id,
            'defaultDateFilter' => $dateFilter,
            'order' => $groupedOrders,
            'waiter' => $waiterDetail,
            'total_sales' => round($totalSales, 2),
            'attendance' => $attendance,
            'commissionData' => $commission,
            'incentiveData' => $incentiveData,
            'configIncentive' => $configIncentive,
            'commissionThisMonth' => $commissionThisMonth,
            'incentiveThisMonth' => $incentiveThisMonth,
            // 'commissionAmt' => round($commissionAmt, 2),
        ]);
   }

   public function salesReport(Request $request, string $id)
   {
        $dateFilter = $request->input('dateFilter');
        $dateFilter = array_map(function ($date) {
            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
        }, $dateFilter);

        $allOrders = OrderItem::whereDate('created_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0])
                                ->when(count($dateFilter) > 1, function($subQuery) use ($dateFilter) {
                                    $subQuery->whereDate('created_at','<=', $dateFilter[1]);
                                })
                                ->with('order','product.productItems.commItems.configComms')
                                ->where('status', 'Served')
                                ->where('type', 'Normal')
                                ->where('user_id', $id)
                                ->orderBy('created_at','desc')
                                ->get();
        $orders = $allOrders->map(function ($order) {
            $commissionAmt = 0;
            $totalAmount = $order->amount;
            $productName = $order->product->product_name;
            $productPrice = $order->product->price;
            $item_qty = $order->item_qty;
            foreach ($order->product->productItems as $productItem) {
                foreach ($productItem->commItems as $commItem) {
                    $rate = $commItem->configComms->rate;
                    $commType = $commItem->configComms->comm_type;
    
                    if ($commType === 'Fixed amount per sold product') {
                        $commissionAmt += $rate * $order->item_qty;
                    } else {
                        $commissionValue = $productPrice * $order->item_qty * ($rate / 100);
                        $commissionAmt += $commissionValue;
                    }
                }
            }
            
            return [
                'created_at' => $order->created_at->format('d/m/Y'),
                'order_id' => $order->id,
                'order_no' => $order->order->order_no,
                'product_name' => $productName,
                'price' => $productPrice,
                'serve_qty' => $item_qty,
                'total_amount' => round($totalAmount, 2), 
                'commission' => round($commissionAmt, 2)
            ];
        });

        // dd($orders);

        $groupedOrders = $orders->groupBy('order_no')->map(function ($groupedItems, $orderNo) {
            $totalSales = $groupedItems->sum('total_amount');
            $totalCommission = $groupedItems->sum('commission');
            $createdAt = $groupedItems->first()['created_at'];
        
            return [
                'created_at' => $createdAt,
                'order_no' => $orderNo,
                'total_amount' => round($totalSales, 2),
                'commission' => round($totalCommission, 2),
                'items' => $groupedItems->toArray()
            ];
        });
        $groupedOrders = array_values($groupedOrders->toArray());

        return response()->json($groupedOrders);
   }

   public function orderDetails(string $id)
   {
        try {
            $orderItems = OrderItem::where('order_id', $id)
                                    ->get(['product_id', 'item_qty', 'amount']);
            if ($orderItems->isEmpty()) {
                return response()->json(['message' => 'No order items found.'], 404);
            }

            $orderDetails = $orderItems->map(function ($item) {
                $product = Product::where('id', $item->product_id)
                                    ->first(['product_name', 'price']);

            if (!$product) {
                return null;
            }

                return [
                    'item_id' => $item->product_id,
                    'product_name' => $product->product_name,
                    'serve_qty' => $item->item_qty,
                    'amount' => $item->amount,
                    'price' => $item->item_qty * $product->price,
                    'commission' => ceil($product->price * $item->item_qty * 0.15),
                ];
            })->filter();

            return response()->json($orderDetails);

        } catch (\Exception $e) {

            return response()->json(['message' => 'Internal Server Error.'], 500);
        }
    }

    public function viewAttendance(Request $request, string $id)
    {
        $dateFilter = $request->input('dateFilter');
        $dateFilter = array_map(function ($date) {
            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
        }, $dateFilter);

        $attendance = WaiterAttendance::whereDate('created_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0] )
                                        ->when(count($dateFilter) > 1, function($subQuery) use ($dateFilter) {
                                            $subQuery->where('created_at','<=', $dateFilter[1]);
                                        })
                                        ->where('user_id', $id)
                                        ->orderBy('created_at','desc')
                                        ->get(['check_in','check_out'])
                                        ->map(function ($record) {
                                            $checkIn = Carbon::parse($record->check_in);
                                            $checkOut = Carbon::parse($record->check_out);
                                    
                                            $durationInSeconds = $checkIn->diffInSeconds($checkOut);
                                    
                                            $hours = floor($durationInSeconds / 3600);
                                            $minutes = floor(($durationInSeconds % 3600) / 60);
                                            $seconds = $durationInSeconds % 60;
                                    
                                            $formattedDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                    
                                            return [
                                                'check_in' => $checkIn->toDateTimeString(),
                                                'check_out' => $checkOut->toDateTimeString(),
                                                'duration' => $formattedDuration,
                                            ];
                                        });

        return response()->json($attendance);
    }

    public function filterSalesPerformance (Request $request)
    {
        $allWaiters = User::where('role', 'waiter')->select('id', 'full_name')->get()->keyBy('id');

        $waitersSalesDetail = OrderItem::with('order.waiter')
                                        ->where('status', 'Served')
                                        ->where('type', 'Normal')
                                        ->when($request->input('selected') === 'This month', function ($query) {
                                            $query->whereMonth('created_at', now()->month)
                                                ->whereYear('created_at', now()->year);
                                        })
                                        ->when($request->input('selected') === 'This year', function ($query) {
                                            $query->whereYear('created_at', now()->year);
                                        })
                                        ->selectRaw('user_id, SUM(amount) as total_sales')
                                        ->groupBy('user_id')
                                        ->get()
                                        ->map(function ($order) {
                                            return [
                                                'waiter_id' => $order->user_id,
                                                'total_sales' => (int)$order->total_sales,
                                            ];
                                        })
                                        ->keyBy('waiter_id');
        $waitersDetail = [];
        foreach ($allWaiters as $waiter) {
            $salesDetail = $waitersSalesDetail->firstWhere('waiter_id', $waiter->id);
            $waitersDetail[] = [
                'waiter_name' => $waiter->full_name,
                'total_sales' => $salesDetail ? (int)$salesDetail['total_sales'] : 0,
            ];
        }

        $waiterIds = array_column($waitersDetail, 'waiter_name');
        $waiterSales = array_column($waitersDetail, 'total_sales');
        
        return response()->json([
            'waiterIds' => $waiterIds,
            'waiterSales' => $waiterSales
        ]);
    }

    public function filterCommEarned (Request $request)
    {
        //step 1: get the purchased items
        $purchased = Order::with(['orderItems.product'])
                            ->when($request->input('selectedFilter') === 'This month', function ($query) {
                                $query->whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year);
                            })
                            ->when($request->input('selectedFilter') === 'This year', function ($query) {
                                $query->whereYear('created_at', now()->year);
                            })
                            ->where('status', 'Order Completed')
                            ->get();
        $waitersList = User::where('role', 'waiter')->get()->keyBy('id'); 
        $result = [];

        foreach ($waitersList as $waiter) {
            $result[$waiter->id] = [
                'waiterId' => $waiter->id,
                'waiterName' => $waiter->full_name,
                'commission' => 0, 
            ];
        }

        // $purchased = Order::with(['orderItems.product'])->get();

        foreach ($purchased as $order) {
            $waiterId = $order->user_id;

            foreach ($order->orderItems as $orderItem) {
                $itemQty = $orderItem->item_qty;
                $productPrice = $orderItem->product->price;
                $commId = ConfigEmployeeCommItem::where('item', $orderItem->product_id)
                                                ->where('created_at', '<=', $orderItem->created_at)
                                                ->pluck('comm_id');

                $commType = ConfigEmployeeComm::whereIn('id', $commId)
                                                ->select('comm_type', 'rate')
                                                ->get()
                                                ->toArray();

                foreach ($commType as $comm) {
                    $rate = $comm['rate']; 
                    $commTypeValue = $comm['comm_type']; 
                    if ($commTypeValue === 'Fixed amount per sold product') {
                        $commission = $rate * $itemQty;
                    } else {
                        $commission = $productPrice * $rate / 100 * $itemQty;
                    }

                    if (isset($result[$waiterId])) {
                        $result[$waiterId]['commission'] += $commission; 
                    }
                }
            }
        }

        $name = array_column($result, 'waiterName');
        $commission = array_column($result, 'commission');

        return response()->json([
            'waiterNames' => $name,
            'waiterCommission' => $commission,
        ]);
    }

}