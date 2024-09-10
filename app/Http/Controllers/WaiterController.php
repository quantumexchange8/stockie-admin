<?php

namespace App\Http\Controllers;
use App\Http\Requests\WaiterRequest;
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

class WaiterController extends Controller
{
   public function waiter(Request $request)
   {
        $waiters = Waiter::all();

        $message = $request->session()->get('message');

        // $request->session()->forget('message');
        // $request->session()->save();

        return Inertia::render('Waiter/Waiter', [
            'waiters'=> $waiters,
            'message'=> $message ?? [],
        ]);
    }


   public function store(WaiterRequest $request)
   {
    // dd($request->validated());
       $user = Waiter::create([
           'name' => $request->name,
           'phone' => '+60' . $request->phone,
           'email'=>$request->email,
           'staffid' => $request->staffid,
           'salary'=>$request->salary,
           'stockie_email'=>$request->stockie_email,
           'stockie_password' => Hash::make($request->stockie_password),
       ]);

       $message = [ 
            'severity' => 'success', 
            'summary' => 'New waiter has been successfully added.'
        ];

       return redirect()->route('waiter')->with(['message' => $message]);
   }

   public function deleteWaiter ($id)
   {
        $deleteWaiter = Waiter::where('id', $id);
        $deleteWaiter->delete();

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Selected waiter has been successfully deleted.'
        ];

        return Redirect::route('waiter')->with(['message' => $message]);
   }

   public function editWaiter (WaiterRequest $request)
   {

        $editWaiter = Waiter::find($request->id);
        $editWaiter->update([
            'name' => $request->input('name'),
            'phone' => '+60' . $request->input('phone'),
            'email' => $request->input('email'),
            'staffid' => $request->input('staffid'),
            'salary' => $request->input('salary'),
            'stockie_email' => $request->input('stockie_email'),
            'stockie_password' => $request->input('stockie_password'),
        ]);

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Changes saved'
        ];
        // dd($request->all());
        return redirect()->route('waiter')->with(['message' => $message]);
   }

   public function showWaiterDetails(string $id)
   {    


        //date filter
        $dateFilter = [
            now()->subDays(7)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
            now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
        ];

        //orders
        $commissionRate = 0.15;
        $allOrders = Order::where('waiter_id', $id)
                ->whereBetween('created_at', $dateFilter)
                ->orderBy('created_at','desc')
                ->get();
        $orders = $allOrders->map(function ($order) use ($commissionRate) {
            $totalAmount = (float) $order->total_amount; 
            $commission = $totalAmount * $commissionRate;
            
            return [
                'created_at' => $order->created_at->format('d/m/Y'),
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'total_amount' => round($totalAmount, 2), 
                'commission' => round($commission, 2)
            ];
        });

        //waiter
        $waiterDetail = Waiter::where('id', $id)->first();
        
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

        //existed incentive thresholds
        $configIncentive = ConfigIncentive::all();

        //incentive
        $totalSales = Order::where('waiter_id', $id)
            ->selectRaw('YEAR(created_at) as year, MONTHNAME(created_at) as month, MONTH(created_at) as month_num, SUM(total_amount) as total_sales, SUM(total_amount) * ? as commission', [$commissionRate])
            ->groupBy('year', 'month', 'month_num')
            ->get();
        
        $incentive = ConfigIncentiveEmployee::where('user_id', $id)->get();
        $incentiveIds = $incentive->pluck('incentive_id');
        $incentiveTypes = ConfigIncentive::whereIn('id', $incentiveIds)->get();
        
        $incentiveData = $incentive->map(function ($incentive) use ($totalSales, $incentiveTypes) {
            $salesForMonth = $totalSales->firstWhere('month_num', $incentive->created_at->month);
            $incentiveType = $incentiveTypes->firstWhere('id', $incentive->incentive_id);
            $incentiveAmount = 0;
            $incentiveRate = null;
            if ($salesForMonth && $salesForMonth->total_sales > $incentiveType->monthly_sale) {
                switch ($incentiveType->type) {
                    case 'fixed':
                        $incentiveAmount = $incentiveType->rate;
                        $incentiveRate = 'RM ' . round($incentiveType->rate, 0);
                        break;
                    case 'percentage':
                        $incentiveAmount = $incentiveType->rate * $salesForMonth->total_sales;
                        $incentiveRate = round($incentiveType->rate * 100, 0) . '%';
                        break;
                }
            }

            $totalCommission = round($incentiveAmount + ($salesForMonth ? $salesForMonth->commission : 0), 2);
            return [
                'id' => $incentive->id,
                'user_id' => $incentive->user_id,
                'status' => $incentive->status,
                'created_by' => $salesForMonth ? $salesForMonth->month . ' ' . $salesForMonth->year : null,
                'total_sales' => $salesForMonth ? round($salesForMonth->total_sales, 2) : null,
                'commission' => $salesForMonth ? round($salesForMonth->commission, 2) : null,
                'total_commission' => $totalCommission ? $totalCommission : 0,
                'incentive' => $incentiveAmount ? round($incentiveAmount, 2) : 0,
                'rate' => $incentiveRate,
            ];
        });
        
        return Inertia::render('Waiter/Partials/WaiterDetails', [
            'id' => $id,
            'defaultDateFilter' => $dateFilter,
            'order' => $orders,
            'waiter' => $waiterDetail,
            'attendance' => $attendance,
            'incentiveData' => $incentiveData,
            'configIncentive' => $configIncentive,
        ]);
   }

   public function salesReport(Request $request, string $id)
   {
        $dateFilter = $request->input('dateFilter');
        $dateFilter = array_map(function ($date) {
            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
        }, $dateFilter);

        $commissionRate = 0.15;
        $allOrders = Order::whereDate('created_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0])
                        ->when(count($dateFilter) > 1, function($subQuery) use ($dateFilter) {
                            $subQuery->whereDate('created_at','<=', $dateFilter[1]);
                        })
                        ->where('waiter_id', $id)
                        ->orderBy('created_at','desc')
                        ->get();
        $orders = $allOrders->map(function ($order) use ($commissionRate) {
            $totalAmount = (float) $order->total_amount; 
            $commission = $totalAmount * $commissionRate;
            
            return [
                'created_at' => $order->created_at->format('d/m/Y'),
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'total_amount' => round($totalAmount, 2), 
                'commission' => round($commission, 2)
            ];
        });

        return response()->json($orders);
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

}