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
   private function geWaitersDetails()
   {
        $waiters = User::where([
                            ['position', 'waiter'],
                            ['status', 'Active']
                        ])
                        ->get();

        $waiters->each(function($waiter){
            $waiter->image = $waiter->getFirstMediaUrl('user');
        });

        //for sales performance graph 
        $allWaiters = $waiters->keyBy('id');
        $waitersDetail = [];

        $waitersSalesDetail = OrderItem::itemSales()
                                        ->whereMonth('orders.created_at', now()->month)
                                        ->whereYear('orders.created_at', now()->year)
                                        ->selectRaw('order_items.user_id as waiter_id, SUM(order_items.amount) as total_sales')
                                        ->groupBy('order_items.user_id')
                                        ->get();

        //for commission earned
        // step 1: get the purchased items
        $result = [];

        foreach ($allWaiters as $waiter) {
            //for sales performance graph 
            $salesDetail = $waitersSalesDetail->firstWhere('waiter_id', $waiter->id);
            $waitersDetail[] = [
                'waiter_name' => $waiter->full_name,
                'total_sales' => $salesDetail ? number_format((float)$salesDetail->total_sales, 2, '.', '') : 0.00,
                'image' => $waiter->image,
            ];

            //for commission earned
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

        //for sales performance graph 
        $waiterIds = array_column($waitersDetail, 'waiter_name');
        $waiterSales = array_column($waitersDetail, 'total_sales');
        $waiterImages = array_column($waitersDetail, 'image');

        //for commission earned
        $name = array_column($result, 'waiterName');
        $commission = array_map(function($value) {
            return (float)number_format(ceil($value * 100) / 100, 2, '.', ',');
        }, array_column($result, 'commission'));

        return [
            'waiters'=> $waiters,
            'waiterIds' => $waiterIds,
            'waiterSales' => $waiterSales,
            'image' => $waiterImages,
            'waiterNames' => $name,
            'waiterCommission' => $commission,
        ];
   }

   public function waiter(Request $request)
   {
        $waitersDetails = $this->geWaitersDetails();

        $message = $request->session()->get('message');

        return Inertia::render('Waiter/Waiter', [
            'waiters'=> $waitersDetails['waiters'],
            'message'=> $message ?? [],
            'waiterIds' => $waitersDetails['waiterIds'],
            'waiterSales' => $waitersDetails['waiterSales'],
            'image' => $waitersDetails['image'],
            'waiterNames' => $waitersDetails['waiterNames'],
            'waiterCommission' => $waitersDetails['waiterCommission'],
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
            'employment_type'=> $validatedData['employment_type'],
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

   public function deleteWaiter(Request $request, string $id)
   {
        // $severity = 'error';  
        // $summary = 'Error deleting selected waiter.';

        $deleteWaiter = User::with('configIncentEmployee')->find($id);

        // activity()->useLog('delete-waiter')
        //             ->performedOn($deleteWaiter)
        //             ->event('deleted')
        //             ->withProperties([
        //                 'edited_by' => auth()->user()->full_name,
        //                 'image' => auth()->user()->getFirstMediaUrl('user'),
        //                 'waiter_name' => $deleteWaiter->full_name,
        //             ])
        //             ->log("Waiter '$deleteWaiter->full_name' is deleted.");

        $deleteWaiter->update(['status' => 'Inactive']);

        foreach ($deleteWaiter->configIncentEmployee as $key => $employee) {
            $employee->update(['status' => 'Inactive']);
            $employee->delete();
        }

        // $severity = 'success';
        // $summary = 'Selected waiter has been successfully deleted.';

        if ($request->actionType === 'update') {
            $waitersDetails = $this->geWaitersDetails();
    
            $data = [
                'waiters'=> $waitersDetails['waiters'],
                'waiterIds' => $waitersDetails['waiterIds'],
                'waiterSales' => $waitersDetails['waiterSales'],
                'image' => $waitersDetails['image'],
                'waiterNames' => $waitersDetails['waiterNames'],
                'waiterCommission' => $waitersDetails['waiterCommission'],
            ];
            
            return response()->json($data);

        } else if ($request->actionType === 'redirect') {
            $message = [ 
                'severity' => 'success', 
                'summary' => 'Selected waiter has been successfully deleted.'
            ];

            return Redirect::route('waiter')->with(['message' => $message]);
        }
        
    //    activity()->useLog('fire')
    //             ->performedOn($deleteWaiter)
    //             ->event('fired')
    //             ->log("$deleteWaiter->full_name is leaving us and we now have $deleteWaiter->salary more to spend, oh yeah");

        // $message = [ 
        //     'severity' => $severity, 
        //     'summary' => $summary
        // ];


        // return Redirect::route('waiter')->with(['message' => $message]);
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
            'employment_type'=> $validatedData['employment_type'],
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
        // $attendance = WaiterAttendance::where('user_id', $id)
        //                                 ->get(['check_in', 'check_out'])
        //                                 ->map(function ($record) {
        //                                     $checkIn = Carbon::parse($record->check_in);
        //                                     $checkOut = Carbon::parse($record->check_out);
    
        //                                     $durationInSeconds = $checkIn->diffInSeconds($checkOut);
                                    
        //                                     $hours = floor($durationInSeconds / 3600);
        //                                     $minutes = floor(($durationInSeconds % 3600) / 60);
        //                                     $seconds = $durationInSeconds % 60;
                                    
        //                                     $formattedDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                    
        //                                     return [
        //                                         'check_in' => $checkIn->toDateTimeString(),
        //                                         'check_out' => $record->check_out ? $checkOut->toDateTimeString() : null,
        //                                         'work_duration' => $formattedDuration,
        //                                     ];
        //                                 });

        //existing incentive thresholds
        $configIncentive = ConfigIncentiveEmployee::where('user_id', $id)
                                                ->with('configIncentive')    
                                                ->select('incentive_id') 
                                                ->distinct() 
                                                ->get();
        
        $waiterSales = $waiterDetail->sales();
        // dd($waiterSales->toSql(), $waiterSales->getBindings());

        // total sales of waiter this month
        $totalSales = $waiterSales->clone()
                                    ->whereMonth('orders.created_at', now()->month)
                                    ->sum('order_items.amount');

        // sales grouped by month
        $totalSalesMonthly = $waiterSales->clone()
                                            ->selectRaw("
                                                YEAR(orders.created_at) as year, 
                                                MONTHNAME(orders.created_at) as month, 
                                                MONTH(orders.created_at) as month_num, 
                                                SUM(order_items.amount) as total_sales
                                            ")
                                            ->groupBy('year', 'month', 'month_num')
                                            ->get();

        // commission
        $commissionItemsIds = $waiterSales->clone()
                                            ->with(['product.commItem.configComms', 'order'])
                                            ->orderBy('order_items.id')
                                            ->get();

        $groupCommissionItemsIds = $commissionItemsIds->groupBy(function($orderItem){
            return $orderItem->order->created_at->format('F Y');
        });

        $commission = $groupCommissionItemsIds->map(function ($groupItems, $monthYear) use ($totalSalesMonthly, $waiterDetail) {
            $commissionMonthNum = $groupItems->first()->created_at->month;
            $matchingSales = $totalSalesMonthly->firstWhere('month_num', $commissionMonthNum);
            $commissionAmt = 0;
            
            foreach ($groupItems as $commissionItemsId) {
                if ($commissionItemsId->product->commItem) {
                    $commissionItemPrice = $commissionItemsId->product->price;
                    $rate = $commissionItemsId->product->commItem->configComms->rate;
                    $commType = $commissionItemsId->product->commItem->configComms->comm_type;
                    $item_qty = $commissionItemsId->item_qty;
                    
                    $commissionAmt += $commType === 'Fixed amount per sold product' 
                            ? $rate * $item_qty
                            : $commissionItemPrice * $item_qty * ($rate / 100);
                }
            }
        
            return [
                'monthly_sale' => $matchingSales->total_sales ?? 0,
                'commissionAmt' => $commissionAmt,
                'created_at' => $monthYear,
                'commissionMonthNum' => $commissionMonthNum,
                'groupItems' => $groupItems
            ];
        })->values()->toArray();
        
        $commissionThisMonth = collect($commission)->firstWhere('created_at', Carbon::now()->format('F Y'))['commissionAmt'] ?? 0;

        //incentive
        $incentiveData = EmployeeIncentive::where('user_id', $id)
                                            ->orderBy('period_start')
                                            ->get();

        //date filter
        $dateFilter = [
            now()->subDays(7)->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s'),
            now()->timezone('Asia/Kuala_Lumpur')->endOfDay()->format('Y-m-d H:i:s'),
        ];

        //orders
        $allOrders = $waiterSales->clone()
                                    ->whereBetween('orders.created_at', $dateFilter)
                                    ->with(['order', 'product.commItem.configComms'])
                                    ->select('order_items.*')
                                    ->orderByDesc('orders.created_at')
                                    ->get();

        $orders = $allOrders->map(function ($order) {
            $product = $order->product;
            $commItem = $product->commItem;
            
            $commissionAmt = $commItem 
                ? ($commItem->configComms->comm_type === 'Fixed amount per sold product'
                    ? $commItem->configComms->rate * $order->item_qty
                    : $product->price * $order->item_qty * ($commItem->configComms->rate / 100))
                : 0;
    
            return [
                'created_at' => $order->order->created_at->format('d/m/Y'),
                'order_id' => $order->id,
                'order_no' => $order->order->order_no,
                'product_name' => $product->product_name,
                'price' => $product->price,
                'serve_qty' => $order->item_qty,
                'total_amount' => round($order->amount, 2), 
                'commission' => round($commissionAmt, 2),
                'image' => $product->getFirstMediaUrl('product')
            ];
        });

        $groupedOrders = $orders->groupBy('order_no')
                                ->map(function ($groupedItems) {
                                    return [
                                        'created_at' => $groupedItems->first()['created_at'],
                                        'order_no' => $groupedItems->first()['order_no'],
                                        'total_amount' => round($groupedItems->sum('total_amount'), 2),
                                        'commission' => round($groupedItems->sum('commission'), 2),
                                        'items' => $groupedItems->toArray()
                                    ];
                                })
                                ->values()
                                ->toArray();

        return Inertia::render('Waiter/Partials/WaiterDetails', [
            'id' => $id,
            'defaultDateFilter' => $dateFilter,
            'order' => $groupedOrders,
            'waiter' => $waiterDetail,
            'total_sales' => round($totalSales, 2),
            // 'attendance' => $attendance,
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
        $dateFilter = collect($request->input('dateFilter'))
                        ->map(fn($date) => Carbon::parse($date)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'))
                        ->toArray();

        $allOrders = User::find($id)
                            ->sales()
                            ->with(['order','product.commItem.configComms'])
                            ->when(count($dateFilter) === 1, fn($query) => 
                                $query->whereDate('orders.created_at', $dateFilter[0])
                            )
                            ->when(count($dateFilter) > 1, fn($query) => 
                                $query->whereDate('orders.created_at', '>=', $dateFilter[0])
                                        ->whereDate('orders.created_at', '<=', $dateFilter[1])
                            )
                            ->select('order_items.*')
                            ->orderByDesc('orders.created_at')
                            ->get();

        $orders = $allOrders->map(function ($order) {
            $product = $order->product;
            $commItem = $product->commItem;
            
            $commissionAmt = $commItem 
                ? ($commItem->configComms->comm_type === 'Fixed amount per sold product'
                    ? $commItem->configComms->rate * $order->item_qty
                    : $product->price * $order->item_qty * ($commItem->configComms->rate / 100))
                : 0;
    
            return [
                'created_at' => $order->order->created_at->format('d/m/Y'),
                'order_id' => $order->id,
                'order_no' => $order->order->order_no,
                'product_name' => $product->product_name,
                'price' => $product->price,
                'serve_qty' => $order->item_qty,
                'total_amount' => round($order->amount, 2), 
                'commission' => round($commissionAmt, 2),
                'image' => $product->getFirstMediaUrl('product')
            ];
        });

        $groupedOrders = $orders->groupBy('order_no')
                                ->map(function ($groupedItems) {
                                    return [
                                        'created_at' => $groupedItems->first()['created_at'],
                                        'order_no' => $groupedItems->first()['order_no'],
                                        'total_amount' => round($groupedItems->sum('total_amount'), 2),
                                        'commission' => round($groupedItems->sum('commission'), 2),
                                        'items' => $groupedItems->toArray()
                                    ];
                                })
                                ->values()
                                ->toArray();

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

    public function getAttendanceList(Request $request, string $id)
    {
        $waiter = User::find($id);

        $dateFilter = array_map(
            fn($date) => Carbon::parse($date)
                ->timezone('Asia/Kuala_Lumpur')
                ->toDateString(),
            $request->input('date_filter')
        );
    
        $startDate = Carbon::parse($dateFilter[0])->startOfDay();
        $endDate = Carbon::parse($dateFilter[1] ?? $dateFilter[0])->endOfDay();
    
        // Step 1: Get only the "Checked out" records within the filter
        $checkIns = WaiterAttendance::where('user_id', $waiter->id)
                                    ->where('status', 'Checked out')
                                    ->where(function($query) use ($startDate, $endDate) {
                                        // Records within date range
                                        $query->whereDate('check_in', '>=', $startDate)
                                                ->whereDate('check_in', '<=', $endDate);
                                    })
                                    ->orderBy('check_in')
                                    ->get();

        $currentCheckIn = WaiterAttendance::where('user_id', $waiter->id)
                                            ->whereIn('status', ['Checked in', 'Checked out'])
                                            ->where(function($query) use ($startDate, $endDate) {
                                                // Records within date range
                                                $query->whereDate('check_in', '>=', $startDate)
                                                        ->whereDate('check_in', '<=', $endDate);
                                            })
                                            ->latest()
                                            ->first();
                                            
        if ($currentCheckIn && $currentCheckIn->status === 'Checked in') {
            $checkIns->push($currentCheckIn);
        }

        // Early return if no records
        if ($checkIns->isEmpty()) {
            return response()->json([]);
        }
                                    
        // Step 2: For each check-in, get all related records (same day or after)
        $groups = $checkIns->map(function ($checkInRecord) use ($waiter, $startDate, $endDate) {
            $relatedRecords = WaiterAttendance::where('user_id', $waiter->id)
                                                ->where(function($q) use ($checkInRecord) {
                                                    // Records within date range
                                                    $q->where('check_in', '>=', $checkInRecord->check_in)
                                                        ->where(function($sq) use ($checkInRecord) {
                                                            $existingCheckOut = $checkInRecord->check_out ?? now();

                                                            $sq->where(function($ssq) use ($existingCheckOut) {
                                                                    $ssq->where('check_in', '<=', $existingCheckOut)
                                                                        ->whereNull('check_out');
                                                                })
                                                                ->orWhereDate('check_out', '<=', $existingCheckOut);
                                                        });
                                                })
                                                ->orderByRaw("
                                                    CASE 
                                                        WHEN status IN ('Break end', 'Checked out') THEN check_out 
                                                        ELSE check_in 
                                                    END
                                                ")
                                                ->get();           

            $currentGroup = null;
        
            // Group records into complete attendance periods
            foreach ($relatedRecords as $record) {
                switch ($record->status) {
                    case 'Checked in':
                        $currentGroup = [
                            'check_in' => $record->check_in,
                            'check_out' => null,
                            'breaks' => [],
                            'date' => Carbon::parse($record->check_in)->format('d/m/Y'),
                            'status' => 'Ongoing'
                        ];
                        break;
                        
                    case 'Checked out':
                        if ($currentGroup) {
                            $currentGroup['check_out'] = $record->check_out;
                            $currentGroup['status'] = 'Completed';
                        }
                        break;
                        
                    case 'Break start':
                    case 'Break end':
                        if ($currentGroup) {
                            $currentGroup['breaks'][] = $record;
                        }
                        break;
                }
            }
            // if (!$currentGroup) dd($currentGroup, $relatedRecords, $checkInRecord);

            // Only return summarized data (no detailed breaks)
            return $this->processAttendanceGroup($currentGroup, $waiter->salary ?? 0, $waiter, false);
        });
        
        return response()->json($groups->sortByDesc('check_in')->values());
    }

    public function getAttendanceListDetail(Request $request, string $id)
    {
        $waiter = User::find($id);

        $targetDate = Carbon::parse($request->input('target_date'))->timezone('Asia/Kuala_Lumpur')->toDateString();
        $startDate = Carbon::parse($targetDate)->startOfDay();
        $endDate = Carbon::parse($targetDate)->endOfDay();
        
        $currentAttendance = WaiterAttendance::where('user_id', $waiter->id)
                                            ->whereIn('status', ['Checked in', 'Checked out'])
                                            ->where(function($query) use ($startDate, $endDate) {
                                                // Records within date range
                                                $query->whereDate('check_in', '>=', $startDate)
                                                        ->whereDate('check_in', '<=', $endDate);
                                            })
                                            ->latest()
                                            ->first();     
                                    
        // For each check-in, get all related records (same day or after)
        $relatedRecords = WaiterAttendance::where('user_id', $waiter->id)
                                            ->where(function($q) use ($currentAttendance) {
                                                // Records within date range
                                                $q->where('check_in', '>=', $currentAttendance->check_in)
                                                    ->where(function($sq) use ($currentAttendance) {
                                                        $existingCheckOut = $currentAttendance->check_out ?? now();

                                                        $sq->where(function($ssq) use ($existingCheckOut) {
                                                                $ssq->where('check_in', '<=', $existingCheckOut)
                                                                    ->whereNull('check_out');
                                                            })
                                                            ->orWhereDate('check_out', '<=', $existingCheckOut);
                                                    });
                                            })
                                            ->orderByRaw("
                                                CASE 
                                                    WHEN status IN ('Break end', 'Checked out') THEN check_out 
                                                    ELSE check_in 
                                                END
                                            ")
                                            ->get();
                                            
        // dd($currentAttendance, $relatedRecords->toArray());
        $currentGroup = null;
    
        // Group records into complete attendance periods
        foreach ($relatedRecords as $record) {
            switch ($record->status) {
                case 'Checked in':
                    $currentGroup = [
                        'check_in' => $record->check_in,
                        'check_out' => null,
                        'breaks' => [],
                        'date' => Carbon::parse($record->check_in)->format('d/m/Y'),
                        'status' => 'Ongoing'
                    ];
                    break;
                    
                case 'Checked out':
                    if ($currentGroup) {
                        $currentGroup['check_out'] = $record->check_out;
                        $currentGroup['status'] = 'Completed';
                    }
                    break;
                    
                case 'Break start':
                case 'Break end':
                    if ($currentGroup) {
                        $currentGroup['breaks'][] = $record;
                    }
                    break;
            }
        }

        // Only return summarized data (no detailed breaks)
        $processedAttendance = $this->processAttendanceGroup($currentGroup, $waiter->salary ?? 0, $waiter, true);
        
        return response()->json($processedAttendance);
    }

    // New helper function to format as "Xh Ym"
    protected function formatHoursMinutes($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return sprintf('%dh %02dm', $hours, $minutes);
    }

    private function processAttendanceGroup(array $group, float $salary, User $waiter, $withBreaks): array
    {
        $endTime = $group['check_out'] ?? now();
        $workDuration = Carbon::parse($group['check_in'])->diffInSeconds($endTime);
        
        // Process breaks
        $breakData = $this->calculateBreakDurations($group['breaks'], $endTime, $withBreaks);
        
        // Calculate earnings
        $payableHours = floor(max(0, $workDuration) / 60) / 60;
        $earnings = $waiter->employment_type === 'Part-time' 
            ? max(0, $payableHours * $salary) 
            : 0.00;

        // dd($group['check_in'], $waiter->shifts);
        // dd(Carbon::parse($group['date'])->timezone('Asia/Kuala_Lumpur')->toDateString());
    
        $processedGroup = [
            'date' => $group['date'],
            'check_in' => $group['check_in'],
            'check_out' => $group['check_out'],
            'status' => $group['status'],
            'work_duration' => $this->formatHoursMinutes($workDuration),
            'break_duration' => $this->formatHoursMinutes($breakData['breakDuration']),
            'earnings' => 'RM '.number_format($earnings, 2),
        ];

        if ($withBreaks) {
            $shift = $waiter->shifts()
                            ->whereDate('date', Carbon::parse($group['check_in']))
                            ->with('shifts:id,shift_name')
                            ->first(['id', 'shift_id']);

            $processedGroup['shift'] = $shift;
            $processedGroup['break'] = $breakData['formattedBreaks'];
        }

        return $processedGroup;
    }
    
    private function calculateBreakDurations(array $breaks, $endTime, $withBreaks): array
    {
        $breakDuration = 0;
        $formattedBreaks = [];
        $currentBreak = null;
        
        // Sort breaks once
        $sortedBreaks = collect($breaks)->sortBy('check_in');
        
        foreach ($sortedBreaks as $break) {
            if ($break->status === 'Break start') {
                if ($currentBreak) {
                    // Handle unclosed break
                    $breakDuration += Carbon::parse($currentBreak['start'])->diffInSeconds($break->check_in);
                }
                $currentBreak = [
                    'id' => $break->id, 
                    'start' => $break->check_in, 
                    'end' => null
                ];
            } 
            elseif ($break->status === 'Break end' && $currentBreak) {
                $currentBreak['end'] = $break->check_out;
                $breakDuration += Carbon::parse($currentBreak['start'])->diffInSeconds($currentBreak['end']);

                if ($withBreaks) ($formattedBreaks[] = $currentBreak);
                $currentBreak = null;
            }
        }
        
        // Handle last unclosed break
        if ($currentBreak) {
            $currentBreak['end'] = null;
            $breakDuration += Carbon::parse($currentBreak['start'])->diffInSeconds($endTime);

            if ($withBreaks) ($formattedBreaks[] = $currentBreak);
        }

        $data = ['breakDuration' => $breakDuration];

        if ($withBreaks) {
            $data['formattedBreaks'] = $formattedBreaks;
        }

        return $data;
    }

    public function filterSalesPerformance (Request $request)
    {
        $allWaiters = User::where('position', 'waiter')
                            ->select('id', 'full_name')
                            ->get()
                            ->keyBy('id');

        $waitersSalesDetail = OrderItem::itemSales()
                                        ->when($request->input('selected') === 'This month', function ($query) {
                                                    $query->whereMonth('orders.created_at', now()->month)
                                                        ->whereYear('orders.created_at', now()->year);
                                                })
                                        ->when($request->input('selected') === 'This year', function ($query) {
                                                    $query->whereYear('orders.created_at', now()->year);
                                                })
                                        ->selectRaw('order_items.user_id as waiter_id, SUM(order_items.amount) as total_sales')
                                        ->groupBy('order_items.user_id')
                                        ->get();

        $waitersDetail = [];
        foreach ($allWaiters as $waiter) {
            $salesDetail = $waitersSalesDetail->firstWhere('waiter_id', $waiter->id);
            $waitersDetail[] = [
                'waiter_name' => $waiter->full_name,
                'total_sales' => $salesDetail ? number_format((float)$salesDetail->total_sales, 2, '.', '') : 0.00,
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
        $purchased = OrderItem::itemSales()
                                ->with('product.commItem.configComms', 'handledBy')
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

                $commission = $commTypeValue === 'Fixed amount per sold product' 
                        ? $rate * $itemQty
                        : $productPrice * $itemQty * ($rate / 100);

                if (isset($result[$waiterId]) && $order->handledBy->position === 'waiter') {
                    $result[$waiterId]['commission'] += $commission; 
                    array_push($result[$waiterId]['items'], $order); 
                }
            }
        }

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