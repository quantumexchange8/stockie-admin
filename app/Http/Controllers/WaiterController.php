<?php

namespace App\Http\Controllers;
use App\Http\Requests\WaiterRequest;
use App\Models\ConfigEmployeeComm;
use App\Models\ConfigEmployeeCommItem;
use App\Models\ConfigIncentiveEmployee;
use App\Models\EmployeeIncentive;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
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
        $waiters = User::where('position', 'waiter')->get();
        $waiters->each(function($waiter){
            $waiter->image = $waiter->getFirstMediaUrl('user');
        });

        $message = $request->session()->get('message');

        //for sales performance graph 
        $allWaiters = User::where('position', 'waiter')
                            ->select('id', 'full_name', 'profile_photo')
                            ->get()
                            ->keyBy('id');

        $waitersSalesDetail = OrderItem::with('order.waiter')
                                        ->whereHas('order', function ($query) {
                                            $query->where('status', 'Order Completed')
                                                    ->whereHas('payment', fn ($subQuery) => $subQuery->where('status', 'Successful'))
                                                    ->whereMonth('created_at', now()->month)
                                                    ->whereYear('created_at', now()->year);
                                        })
                                        ->where([
                                            ['status', 'Served'],
                                            ['type', 'Normal']
                                        ])
                                        ->selectRaw('user_id, SUM(amount) as total_sales')
                                        ->groupBy('user_id')
                                        ->get()
                                        ->map(function ($order) {
                                            return [
                                                'waiter_id' => $order->user_id,
                                                'total_sales' => $order->total_sales
                                            ];
                                        })
                                        ->keyBy('waiter_id');


        $waitersDetail = [];
        foreach ($allWaiters as $waiter) {
            $salesDetail = $waitersSalesDetail->firstWhere('waiter_id', $waiter->id);
            $waitersDetail[] = [
                'waiter_name' => $waiter->full_name,
                'total_sales' => $salesDetail ? number_format((float)$salesDetail['total_sales'] - 0.97, 2, '.', '') : 0.00,
                'image' => $waiter->getFirstMediaUrl('user'),
            ];
        }

        $waiterIds = array_column($waitersDetail, 'waiter_name');
        $waiterSales = array_column($waitersDetail, 'total_sales');
        $waiterImages = array_column($waitersDetail, 'image');
        // dd($waiterSales);

        //for commission earned
        // // step 1: get the purchased items
        // $purchased = OrderItem::with('product.commItem.configComms', 'handledBy')
        //                         ->whereHas('order', function ($query) {
        //                             $query->where('status', 'Order Completed')
        //                                     ->whereHas('payment', fn ($subQuery) => $subQuery->where('status', 'Successful'))
        //                                     ->whereMonth('created_at', now()->month)
        //                                     ->whereYear('created_at', now()->year);
        //                         })
        //                         ->where([
        //                             ['status', 'Served'],
        //                             ['type', 'Normal']
        //                         ])
        //                         // ->whereMonth('created_at', now()->month)
        //                         // ->whereYear('created_at', now()->year)
        //                         ->get(['id', 'product_id', 'user_id', 'order_id', 'item_qty', 'created_at']);
        
        $waitersList = User::where('position', 'waiter')->get()->keyBy('id'); 
        $result = [];

        //prepare for each waiter
        foreach ($waitersList as $waiter) {
            $commissionsAmount = $waiter->commissions()
                                    ->whereHas('orderItem.order', function ($query) {
                                        $query->whereMonth('created_at', now()->month)
                                                ->whereYear('created_at', now()->year);
                                    })
                                    ->get()
                                    ->reduce(fn ($total, $item) => $total + $item->amount, 0);

            $result[$waiter->id] = [
                'waiterId' => $waiter->id,
                'waiterName' => $waiter->full_name,
                'commission' => $commissionsAmount, 
            ];
        }
        // dd($result); 
        // $temp = 0;

        // foreach ($purchased as $order) {
        //     $waiterId = $order->user_id;
        //     $itemQty = $order->item_qty;
        //     $productPrice = $order->product->price;
            
        //     $commId = ConfigEmployeeCommItem::where('item', $order->product_id)
        //                                     ->where('created_at', '<=', $order->created_at)
        //                                     ->pluck('comm_id');

        //     $commType = ConfigEmployeeComm::whereIn('id', $commId)
        //                                     ->select('id', 'comm_type', 'rate')
        //                                     ->get()
        //                                     ->toArray();

        //     // dd($commType);
        //     foreach ($commType as $comm) {
        //         // Log::info($order->handledBy->position);
        //         $rate = $comm['rate']; 
        //         $commTypeValue = $comm['comm_type']; 
        //         // if ($commTypeValue === 'Fixed amount per sold product') {
        //         //     $commission = $rate * $itemQty;
        //         //     $temp += $rate * $itemQty;
        //         // } else {
        //         //     $commission = $productPrice * $rate / 100 * $itemQty;
        //         // }

        //         $commission = $commTypeValue === 'Fixed amount per sold product' 
        //                 ? $rate * $itemQty
        //                 : $productPrice * $itemQty * ($rate / 100);

        //         // if ($commTypeValue === 'Fixed amount per sold product') $temp += $rate * $itemQty;

        //         if (isset($result[$waiterId]) && $order->handledBy->position === 'waiter') {
        //             // $result[$waiterId]['commission'] += $commission; 
        //         }
        //     }
        // }

        $name = array_column($result, 'waiterName');
        $commission = array_map(function($value) {
            return (float)number_format(ceil($value * 100) / 100, 2, '.', ',');
        }, array_column($result, 'commission'));


        // dd($waiterSales);
        return Inertia::render('Waiter/Waiter', [
            'waiters'=> $waiters,
            'message'=> $message ?? [],
            'waiterIds' => $waiterIds,
            'waiterSales' => $waiterSales,
            'image' => $waiterImages,
            'waiterNames' => $name,
            'waiterCommission' => $commission,
        ]);
    }


   public function store(WaiterRequest $request)
   {    
        // $request->validate([
        //     'name' => 'required|string|max:255',
        //     'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
        // ]);
        $validatedData = $request->validated();

        $newWaiter = User::create([
            'name' => $validatedData['full_name'],
            'full_name' => $validatedData['full_name'],
            'phone' => $validatedData['phone'],
            'email'=>$validatedData['email'],
            'role_id' => $validatedData['role_id'],
            'position' => 'waiter',
            'salary'=> $validatedData['salary'],
            'worker_email' => $validatedData['stockie_email'],
            'password' => Hash::make($validatedData['password']),
            'profile_photo' => $validatedData['image'],
            'passcode' => $validatedData['passcode'],
        ]);

       if($request->hasFile('image')){
            $newWaiter->addMedia($request->image)->toMediaCollection('user');
       }

       activity()->useLog('create-waiter')
                ->performedOn($newWaiter)
                ->event('added')
                ->withProperties([
                    'created_by' => auth()->user()->full_name,
                    'image' => auth()->user()->getFirstMediaUrl('user'),
                    'waiter_name' => $newWaiter->name,
                ])
                ->log("Waiter '$newWaiter->name' is added.");

       return redirect()->route('waiter');
   }

   public function deleteWaiter (String $id)
   {
        $severity = 'error';  
        $summary = 'Error deleting selected waiter.';

        if ($id) {
            $deleteWaiter = User::find($id);

            activity()->useLog('delete-waiter')
                        ->performedOn($deleteWaiter)
                        ->event('deleted')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'waiter_name' => $deleteWaiter->full_name,
                        ])
                        ->log("Waiter '$deleteWaiter->full_name' is deleted.");

            $deleteWaiter->delete();

            $severity = 'success';
            $summary = 'Selected waiter has been successfully deleted.';
        }
        
    //    activity()->useLog('fire')
    //             ->performedOn($deleteWaiter)
    //             ->event('fired')
    //             ->log("$deleteWaiter->full_name is leaving us and we now have $deleteWaiter->salary more to spend, oh yeah");

        $message = [ 
            'severity' => $severity, 
            'summary' => $summary
        ];


        return Redirect::route('waiter')->with(['message' => $message]);
   }

   public function editWaiter (WaiterRequest $request)
   {
        $validatedData = $request->validated();
        
        $waiter = User::where('id', $request->id)->first();

        $waiter->update([
            'name' => $validatedData['full_name'],
            'full_name' => $validatedData['full_name'],
            'phone' => $validatedData['phone'],
            'email'=>$validatedData['email'],
            'role_id' => $validatedData['role_id'],
            'salary'=> $validatedData['salary'],
            'worker_email' => $validatedData['stockie_email'],
            'profile_photo' => $validatedData['image'],
            'passcode' => $validatedData['passcode'],
        ]);

        activity()->useLog('edit-waiter-detail')
                    ->performedOn($waiter)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'waiter_name' => $waiter->full_name,
                    ])
                    ->log("Waiter $waiter->full_name's detail is updated.");

        if ($validatedData['password'] != '') {
            $waiter->update([
                'password' => Hash::make($validatedData['password']),
            ]);
        }

        if($request->hasFile('image')){
            $waiter->clearMediaCollection('user');
            $waiter->addMedia($request->image)->toMediaCollection('user');
        }

        return redirect()->back();
   }

   public function showWaiterDetails(string $id)
   {    
        //waiter
        $waiterDetail = User::find($id);
        $waiterDetail->image = $waiterDetail->getFirstMediaUrl('user');
        
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
                                                'check_out' => $record->check_out ? $checkOut->toDateTimeString() : null,
                                                'duration' => $formattedDuration,
                                            ];
                                        });

        //existing incentive thresholds
        $configIncentive = ConfigIncentiveEmployee::where('user_id', $id)
                                                ->with('configIncentive')    
                                                ->select('incentive_id') 
                                                ->distinct() 
                                                ->get();

        // $orderItemQuery = OrderItem::query()
        //                             ->join('orders', function ($join) {
        //                                 $join->on('order_items.order_id', '=', 'orders.id')
        //                                         ->where('orders.status', 'Order Completed');
        //                             })
        //                             ->join('payments', function ($join) {
        //                                 $join->on('orders.id', '=', 'payments.order_id')
        //                                         ->where('payments.status', 'Successful');
        //                             })
        //                             ->where([
        //                                 ['order_items.status', 'Served'],
        //                                 ['order_items.type', 'Normal'],
        //                                 ['order_items.user_id', $id]
        //                             ]);
        
        $waiterSales = $waiterDetail->itemSales();

        // //total sales of waiter this month
        // $totalSales = $orderItemQuery->clone()
        //                                 ->whereMonth('orders.created_at', Carbon::now()->month)
        //                                 ->sum('order_items.amount');

        $totalSales = $waiterSales->clone()
                                    ->whereHas('order', fn ($query) => $query->whereMonth('created_at', now()->month))
                                    ->sum('amount');

        // // sales grouped by month
        // $totalSalesMonthly = $orderItemQuery->clone()
        //                                     ->selectRaw("
        //                                         YEAR(orders.created_at) as year, 
        //                                         MONTHNAME(orders.created_at) as month, 
        //                                         MONTH(orders.created_at) as month_num, 
        //                                         SUM(order_items.amount) as total_sales
        //                                     ")
        //                                     ->groupBy('year', 'month', 'month_num')
        //                                     ->get();
        
        $totalSalesMonthly = OrderItem::query()
                                        ->join('orders', function ($join) {
                                            $join->on('order_items.order_id', '=', 'orders.id')
                                                    ->where('orders.status', 'Order Completed');
                                        })
                                        ->join('payments', function ($join) {
                                            $join->on('orders.id', '=', 'payments.order_id')
                                                    ->where('payments.status', 'Successful');
                                        })
                                        ->where([
                                            ['order_items.status', 'Served'],
                                            ['order_items.type', 'Normal'],
                                            ['order_items.user_id', $id]
                                        ])
                                        ->selectRaw("
                                            YEAR(orders.created_at) as year, 
                                            MONTHNAME(orders.created_at) as month, 
                                            MONTH(orders.created_at) as month_num, 
                                            SUM(order_items.amount) as total_sales
                                        ")
                                        ->groupBy('year', 'month', 'month_num')
                                        ->get();

        // //commission
        // $commissionItemsIds = $orderItemQuery->clone()
        //                                         ->with('product.commItem.configComms')
        //                                         ->orderBy('order_items.id')
        //                                         ->get();

        $commissionItemsIds = $waiterSales->clone()
                                    ->with(['product.commItem.configComms', 'order'])
                                    ->orderBy('id')
                                    ->get();

        $groupCommissionItemsIds = $commissionItemsIds->groupBy(function($orderItem){
            return $orderItem->order->created_at->format('F Y');
        });

        $commission = $groupCommissionItemsIds->map(function ($groupItems, $monthYear) use ($totalSalesMonthly, $waiterDetail) {
            $commissionMonthNum = $groupItems->first()->created_at->month;
            $matchingSales = $totalSalesMonthly->firstWhere('month_num', $commissionMonthNum);
            $commissionAmt = 0;
            
            // $commissionAmt = $waiterDetail->commissions()
            //                         ->whereHas('orderItem.order', function ($query) use ($commissionMonthNum, $monthYear) {
            //                             $query->whereMonth('created_at', $commissionMonthNum)
            //                                     ->whereYear('created_at', $monthYear);
            //                         })
            //                         ->get()
            //                         ->reduce(fn ($total, $item) => $total + $item->amount, 0);
            // $temp = [];
            foreach ($groupItems as $commissionItemsId) {
                // foreach ($commissionItemsId->product->productItems as $productItem) {
                //     foreach ($productItem->commItems as $commItem) {
                //         $commissionItemPrice = $commissionItemsId->product->price;
                //         $rate = $commItem->configComms->rate;
                //         $commType = $commItem->configComms->comm_type;
                //         $item_qty = $commissionItemsId->item_qty;
                        
                //         if ($commType === 'Fixed amount per sold product') {
                //             $commissionAmt += $rate * $item_qty;
                //         } else {
                //             $commissionValue = $commissionItemPrice * $item_qty * ($rate / 100);
                //             $commissionAmt += $commissionValue;
                //         }
                //     }
                // }
                
                if ($commissionItemsId->product->commItem) {
                    $commissionItemPrice = $commissionItemsId->product->price;
                    $rate = $commissionItemsId->product->commItem->configComms->rate;
                    $commType = $commissionItemsId->product->commItem->configComms->comm_type;
                    $item_qty = $commissionItemsId->item_qty;
                    
                    // if ($commType === 'Fixed amount per sold product') {
                    //     $commissionAmt += $rate * $item_qty;
                    // } else {
                    //     $commissionValue = $commissionItemPrice * $item_qty * ($rate / 100);
                    //     $commissionAmt += $commissionValue;
                    // }
                    
                    $commissionAmt += $commType === 'Fixed amount per sold product' 
                            ? $rate * $item_qty
                            : $commissionItemPrice * $item_qty * ($rate / 100);

                    // $tempamt = $commType === 'Fixed amount per sold product' 
                    //         ? $rate * $item_qty
                    //         : $commissionItemPrice * $item_qty * ($rate / 100);
                            
                    // array_push($temp, [
                    //     'order_item_id' => $commissionItemsId->id,
                    //     'price' => $commissionItemPrice,
                    //     'rate' => $rate,
                    //     'item_qty' => $item_qty,
                    //     'tempamt' => $tempamt,
                    // ]);
                }
            }
            // if ($commissionMonthNum == 1) dd($temp);
        
            return [
                'monthly_sale' => $matchingSales->total_sales ?? 0,
                'commissionAmt' => $commissionAmt,
                'created_at' => $monthYear,
                'commissionMonthNum' => $commissionMonthNum,
                'groupItems' => $groupItems
            ];
        })->values()->toArray();
        
        $commissionThisMonth = collect($commission)->firstWhere('created_at', Carbon::now()->format('F Y'))['commissionAmt'] ?? 0;

        // $commissions = $waiterDetail->commissions()
        //                             ->whereHas('orderItem.order', function ($query) {
        //                                 $query->whereMonth('created_at', now()->month)
        //                                         ->whereYear('created_at', now()->year);
        //                             })
        //                             ->get()
        //                             ->groupBy(function($comm){
        //                                 return $comm->created_at->format('F Y');
        //                             });

        //incentive
        // $incentive = ConfigIncentiveEmployee::where('user_id', $id)
        //                                     ->with('configIncentive')
        //                                     ->orderBy('created_at')
        //                                     ->get();

        // $groupIncentive = $incentive->groupBy(function($incentiveMonth) {
        //     return $incentiveMonth->created_at->format('F Y');
        // });
        
        // $incentiveData = $groupIncentive->map(function ($groupItems, $monthYear) use ($totalSalesMonthly) {
        //     $incentiveMonthNum = $groupItems->first()->created_at->month;

        //     // Find matching sales for the same month
        //     $matchingSales = $totalSalesMonthly->firstWhere('month_num', $incentiveMonthNum);
        
        //     // filter and keep the incentives with reached monthly sale
        //     $validIncentives = $groupItems->filter(function($incentive) use ($matchingSales) {
        //         return $matchingSales && $matchingSales->total_sales > $incentive->configIncentive->monthly_sale;
        //     });
            
        //     if ($validIncentives->isEmpty()) {
        //         return null;
        //     }
        
        //     // among the reached monthly sale get the highest one
        //     $incentive = $validIncentives->sortByDesc(function($incentive) {
        //         return $incentive->configIncentive->monthly_sale;
        //     })->first();
        
        //     //calculate incentive
        //     $rate = $incentive?->configIncentive->rate ?? 0;
        //     $type = $incentive?->configIncentive->type;
    
        //     if ($incentive) {
        //         $incentiveAmt = $type === 'fixed' ? $rate : $rate * $matchingSales->total_sales;
        //     } else {
        //         $incentiveAmt = 0;
        //     }
        
        //     return [
        //         'incentiveId' => $incentive?->incentive_id,
        //         'monthYear' => $monthYear,
        //         'type' => $type,
        //         'status' => $incentive?->status ?? 'Pending',
        //         'rate' => $rate,
        //         'incentiveAmt' => round($incentiveAmt, 2),
        //         'totalSales' => $matchingSales->total_sales ?? 0,
        //     ];
        // })->filter()->values()->toArray();

        $incentiveData = EmployeeIncentive::where('user_id', $id)
                                            ->orderBy('period_start')
                                            ->get();

        // dd($incentiveData);

        //date filter
        $dateFilter = [
            Carbon::now()->subDays(7)->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s'),
            Carbon::now()->timezone('Asia/Kuala_Lumpur')->endOfDay()->format('Y-m-d H:i:s'),
        ];

        //orders
        $allOrders = OrderItem::query()
                                ->join('orders', function ($join) {
                                    $join->on('order_items.order_id', '=', 'orders.id')
                                            ->where('orders.status', 'Order Completed');
                                })
                                ->join('payments', function ($join) {
                                    $join->on('orders.id', '=', 'payments.order_id')
                                            ->where('payments.status', 'Successful');
                                })
                                ->where([
                                    ['order_items.status', 'Served'],
                                    ['order_items.type', 'Normal'],
                                    ['order_items.user_id', $id]
                                ])
                                ->select('order_items.*')
                                ->whereBetween('orders.created_at', $dateFilter)
                                ->with('order', 'product.commItem.configComms')
                                ->orderBy('orders.created_at','desc')
                                ->get();

        $orders = $allOrders->map(function ($order) {
            $productPrice = $order->product->price;
            $item_qty = $order->item_qty;
            // foreach ($order->product->productItems as $productItem) {
            //     foreach ($productItem->commItems as $commItem) {
            //         $rate = $commItem->configComms->rate;
            //         $commType = $commItem->configComms->comm_type;
    
            //         if ($commType === 'Fixed amount per sold product') {
            //             $commissionAmt += $rate * $order->item_qty;
            //         } else {
            //             $commissionValue = $productPrice * $order->item_qty * ($rate / 100);
            //             $commissionAmt += $commissionValue;
            //         }
            //     }
            // }

            $rate = $order->product->commItem?->configComms->rate;
            $commType = $order->product->commItem?->configComms->comm_type;

            // if ($commType === 'Fixed amount per sold product') {
            //     $commissionAmt += $rate * $order->item_qty;
            // } else {
            //     $commissionValue = $productPrice * $order->item_qty * ($rate / 100);
            //     $commissionAmt += $commissionValue;
            // }
            
            if ($order->product->commItem) {
                $commissionAmt = $commType === 'Fixed amount per sold product' 
                        ? $rate * $item_qty
                        : $productPrice * $item_qty * ($rate / 100);
            } else {
                $commissionAmt = 0;
            }
            
            return [
                'created_at' => $order->created_at->format('d/m/Y'),
                'order_id' => $order->id,
                'order_no' => $order->order->order_no,
                'product_name' => $order->product->product_name,
                'price' => $productPrice,
                'serve_qty' => $item_qty,
                'total_amount' => round($order->amount, 2), 
                'commission' => round($commissionAmt, 2),
                'image' => $order->product->getFirstMediaUrl('product'),
            ];
        });

        $groupedOrders = $orders->groupBy('order_no')->map(function ($groupedItems, $orderNo) {
            $totalSales = $groupedItems->sum('total_amount');
            $totalCommission = $groupedItems->sum('commission');
            $createdAt = $groupedItems->first()['created_at'];
        
            return [
                'created_at' => $createdAt,
                'order_no' => $orderNo,
                'total_amount' => round($totalSales, 2),
                'commission' => round($totalCommission, 2),
                'items' => $groupedItems->toArray(),
            ];
        });
        $groupedOrders = array_values($groupedOrders->toArray());

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
        ]);
   }

   public function viewEmployeeIncentive()
   {
        return redirect()->route('configurations')->with(['selectedTab' => 2]);
   }

   public function salesReport(Request $request, string $id)
   {
        $dateFilter = $request->input('dateFilter');
        $dateFilter = array_map(function ($date) {
            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
        }, $dateFilter);

        $allOrders = OrderItem::whereHas('order', function ($query) use ($dateFilter) {
                                    $query->where('status', 'Order Completed')
                                            ->whereHas('payment', fn ($subQuery) => $subQuery->where('status', 'Successful'))
                                            ->whereDate('created_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0]);
                                })
                                ->when(count($dateFilter) > 1, function($subQuery) use ($dateFilter) {
                                    $subQuery->whereDate('created_at','<=', $dateFilter[1]);
                                })
                                ->with('order','product.commItem.configComms')
                                ->where([  
                                    ['status', 'Served'],
                                    ['type', 'Normal'],
                                    ['user_id', $id]
                                ])
                                ->orderBy('created_at','desc')
                                ->get();

        $orders = $allOrders->map(function ($order) {
            // $commissionAmt = 0;
            $productPrice = $order->product->price;
            $item_qty = $order->item_qty;
            // foreach ($order->product->productItems as $productItem) {
            //     foreach ($productItem->commItems as $commItem) {
            //         $rate = $commItem->configComms->rate;
            //         $commType = $commItem->configComms->comm_type;
    
            //         if ($commType === 'Fixed amount per sold product') {
            //             $commissionAmt += $rate * $order->item_qty;
            //         } else {
            //             $commissionValue = $productPrice * $order->item_qty * ($rate / 100);
            //             $commissionAmt += $commissionValue;
            //         }
            //     }
            // }

            $commType = $order->product->commItem?->configComms->comm_type;
            
            if ($order->product->commItem) {
                $rate = $order->product->commItem->configComms->rate;
                $commissionAmt = $commType === 'Fixed amount per sold product' 
                        ? $rate * $item_qty
                        : $productPrice * $item_qty * ($rate / 100);

            // if ($commType === 'Fixed amount per sold product') {
            //     $commissionAmt += $rate * $item_qty;
            // } else {
            //     $commissionValue = $productPrice * $item_qty * ($rate / 100);
            //     $commissionAmt += $commissionValue;
            // }
            
            } else {
                $commissionAmt = 0;
            }
            
            return [
                'created_at' => $order->order->created_at->format('d/m/Y'),
                'order_id' => $order->id,
                'order_no' => $order->order->order_no,
                'product_name' => $order->product->product_name,
                'price' => $productPrice,
                'serve_qty' => $item_qty,
                'total_amount' => round($order->amount, 2), 
                'commission' => round($commissionAmt, 2),
                'image' => $order->product->getFirstMediaUrl('product')
            ];
        });

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

//    public function orderDetails(string $id)
//    {
//         try {
//             $orderItems = OrderItem::where('order_id', $id)
//                                     ->get(['product_id', 'item_qty', 'amount']);
//             if ($orderItems->isEmpty()) {
//                 return response()->json(['message' => 'No order items found.'], 404);
//             }

//             $orderDetails = $orderItems->map(function ($item) {
//                 $product = Product::where('id', $item->product_id)
//                                     ->first(['product_name', 'price']);

//             if (!$product) {
//                 return null;
//             }

//                 return [
//                     'item_id' => $item->product_id,
//                     'product_name' => $product->product_name,
//                     'serve_qty' => $item->item_qty,
//                     'amount' => $item->amount,
//                     'price' => $item->item_qty * $product->price,
//                     'commission' => ceil($product->price * $item->item_qty * 0.15),
//                 ];
//             })->filter();

//             return response()->json($orderDetails);

//         } catch (\Exception $e) {

//             return response()->json(['message' => 'Internal Server Error.'], 500);
//         }
//     }

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
                                    
                                            if ($record->id === 3) dd($checkOut);
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
        $allWaiters = User::where('position', 'waiter')->select('id', 'full_name')->get()->keyBy('id');

        $waitersSalesDetail = OrderItem::query()
                                        ->join('orders', function ($join) {
                                            $join->on('order_items.order_id', '=', 'orders.id')
                                                    ->where('orders.status', 'Order Completed');
                                        })
                                        ->join('payments', function ($join) {
                                            $join->on('orders.id', '=', 'payments.order_id')
                                                    ->where('payments.status', 'Successful');
                                        })
                                        ->with('order.waiter')
                                        ->where([
                                            ['order_items.status', 'Served'],
                                            ['order_items.type', 'Normal']
                                        ])
                                        ->when($request->input('selected') === 'This month', function ($query) {
                                                    $query->whereMonth('orders.created_at', now()->month)
                                                        ->whereYear('orders.created_at', now()->year);
                                                })
                                        ->when($request->input('selected') === 'This year', function ($query) {
                                                    $query->whereYear('orders.created_at', now()->year);
                                                })
                                        ->selectRaw('order_items.user_id, SUM(order_items.amount) as total_sales')
                                        ->groupBy('order_items.user_id')
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
        $purchased = OrderItem::query()
                                ->join('orders', function ($join) {
                                    $join->on('order_items.order_id', '=', 'orders.id')
                                            ->where('orders.status', 'Order Completed');
                                })
                                ->join('payments', function ($join) {
                                    $join->on('orders.id', '=', 'payments.order_id')
                                            ->where('payments.status', 'Successful');
                                })
                                ->with('product.commItem.configComms', 'handledBy')
                                ->where([
                                    ['order_items.status', 'Served'],
                                    ['order_items.type', 'Normal']
                                ])
                                ->when($request->input('selectedFilter') === 'This month', function ($query) {
                                    $query->whereMonth('orders.created_at', now()->month)
                                        ->whereYear('orders.created_at', now()->year);
                                })
                                ->when($request->input('selectedFilter') === 'This year', function ($query) {
                                    $query->whereYear('orders.created_at', now()->year);
                                })
                                ->orderBy('order_items.id')
                                ->get('order_items.*');
        
        $waitersList = User::where('position', 'waiter')->get()->keyBy('id'); 
        $result = [];

        //prepare for each waiter
        foreach ($waitersList as $waiter) {
            $result[$waiter->id] = [
                'waiterId' => $waiter->id,
                'waiterName' => $waiter->full_name,
                'commission' => 0, 
                'items' => []
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
                // if ($commTypeValue === 'Fixed amount per sold product') {
                //     $commission = $rate * $itemQty;
                // } else {
                //     $commission = $productPrice * $rate / 100 * $itemQty;
                // }

                $commission = $commTypeValue === 'Fixed amount per sold product' 
                        ? $rate * $itemQty
                        : $productPrice * $itemQty * ($rate / 100);

                if (isset($result[$waiterId]) && $order->handledBy->position === 'waiter') {
                    $result[$waiterId]['commission'] += $commission; 
                    array_push($result[$waiterId]['items'], $order); 
                }
            }
        }
        // dd($result);

        $name = array_column($result, 'waiterName');
        $commission = array_map(function($value) {
            return (float)number_format(ceil($value * 100) / 100, 2, '.', ',');
        }, array_column($result, 'commission'));

        return response()->json([
            'waiterNames' => $name,
            'waiterCommission' => $commission,
        ]);
    }

}