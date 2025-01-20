<?php

namespace App\Http\Controllers;

use App\Models\ConfigIncentive;
use App\Models\ConfigIncentiveEmployee;
use App\Models\EmployeeIncentive;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\TotalSalesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Log;

class ConfigEmployeeIncProgController extends Controller
{
    protected $incentiveRecurringDay;
    
    public function __construct()
    {
        $this->incentiveRecurringDay = Setting::where('name', 'Recurring Day')
                                                ->get(['id', 'value'])
                                                ->first();

        $this->incentiveRecurringDay['value'] = (int) $this->incentiveRecurringDay['value'];
    }

    public function index()
    {
        $allWaiters = User::select('id', 'full_name')->where('position', 'waiter')->orderBy('full_name')->get();
        $allWaiters->each(function ($waiter){
            $waiter->image = $waiter->getFirstMediaUrl('user');
        });

        $incentiveProg = ConfigIncentive::with('incentiveEmployees.waiters')
                                        ->get()
                                        ->map(function ($incentive) {
                                            return [
                                                'id' => $incentive->id,
                                                'type' => $incentive->type,
                                                'rate' => $incentive->rate,
                                                'effective_date' => $incentive->effective_date,
                                                'recurring_on' => $incentive->recurring_on,
                                                'monthly_sale' => $incentive->monthly_sale,
                                                'entitled' => $incentive->incentiveEmployees->flatMap(function ($employee) {
                                                    return $employee->waiters->map(function ($waiter) {
                                                        return [
                                                            'id' => $waiter->id ?? null,
                                                            'name' => $waiter->full_name ?? null,
                                                            'image' => $waiter->getFirstMediaUrl('user') ?? null,
                                                        ];
                                                    });
                                                })->unique('id')->sortBy('name')->values(),
                                            ];
                                        });

        return response()->json([
            'incentiveProg' => $incentiveProg,
            'waiters' => $allWaiters,
            'incentiveRecurringDay' => $this->incentiveRecurringDay['value']
        ]);
    }

    public function addAchievement(Request $request)
    {
        // $validatedData = $request->validate([
        //     'comm_type' => 'required|string',
        //     'rate' => 'required|string',
        //     'effective_date' => 'required|date',
        //     'recurring_on' => 'required',
        //     'monthly_sale' => 'required',
        //     'entitled' => 'required',
        // ], [
        //     'remark.required' => 'The remark field is required.',
        //     'remark.string' => 'The remark must be a valid string.',
        //     'remark_description.max' => 'The remark description may not be greater than 255 characters.',
        // ]);

        $rate = $request->comm_type['value'] == 'fixed' ? $request->rate : $request->rate / 100;

        $incentive = ConfigIncentive::create([
            'type' => $request->comm_type['value'],
            'rate' => $rate,
            'effective_date' => Carbon::parse($request->effective_date)->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s'),
            'recurring_on' => $this->incentiveRecurringDay['value'],
            // 'recurring_on' => $request->recurring_on['value'],
            'monthly_sale' => round($request->monthly_sale, 2),
        ]);

        foreach ($request->entitled as $entitledWaiters) {
            ConfigIncentiveEmployee::create([
                'incentive_id' => $incentive->id,
                'user_id' => $entitledWaiters,
                'status' => 'Pending',
            ]);
        }
        return redirect()->back();
    }

    public function deleteAchievement(String $id)
    {
        ConfigIncentiveEmployee::where('incentive_id', $id)->delete();
        ConfigIncentive::find($id)->delete();

        // $message = [
        //     'severity' => 'success',
        //     'summary' => 'Achievement has been deleted.'
        // ];

        return redirect()->route('configurations');
    }

    public function editAchievement(Request $request)
    {
        // if($request->comm_type['value'] == 'fixed')
        // {
        //     $rate = $request->rate;
        // } else {
        //     $rate = $request->rate / 100;
        // }

        ConfigIncentive::find($request->id)->update([
            'type' => $request->comm_type['value'],
            'rate' => $request->comm_type['value'] === 'fixed' ? $request->rate : round($request->rate / 100, 2),
            'effective_date' => $request->effective_date,
            'recurring_on' => $this->incentiveRecurringDay['value'],
            // 'recurring_on' => $request->recurring_on['value'],
            'monthly_sale' => (float)$request->monthly_sale,
        ]);

    }

    public function incentCommDetail(String $id)
    {
        $incentive = ConfigIncentive::with('incentiveEmployees')->find($id);

        // get related waiters
        $entitled = ConfigIncentiveEmployee::where('incentive_id', $id)
                                            ->orderBy('created_at')
                                            // ->where('created_at', '>=', $incentive->effective_date)
                                            ->with([
                                                'configIncentive',
                                                'waiters:id,full_name',
                                                'waiters.itemSales'
                                                // 'waiters.orderedItems' => function($query) use ($incentive) {
                                                //     $query->where([
                                                //         ['status', 'Served'],
                                                //         ['type', 'Normal'],
                                                //         // ['created_at', '>=', $incentive->effective_date],
                                                //     ]);
                                                // }
                                            ])
                                            ->get();
        // dd($entitled);

        //group orders according to recurring date
        // $entitled->map(function($entitleds){
        //     $entitleds->waiters->map(function($orderItems){

        //     })
        // })

        // // waiter details (name, id, total_sales)
        // $entitled = $entitled->map(function ($item) {
        //     return $item->waiters->map(function ($waiter) {
        //         return [
        //             'id' => $waiter->id,
        //             'full_name' => $waiter->full_name,
        //             'total_sales' => TotalSalesService::getSales($waiter->id),
        //         ];
        //     });
        // })->flatten(1)->unique('id');

        // dd($entitled);
        /**/
        // $sales = OrderItem::with(['handledBy' => fn($query) => $query->where('position', 'waiter')])
        //                     ->where([
        //                         ['status', 'Served'],
        //                         ['type', 'Normal'],
        //                         ['created_at', '>=', $incentive->effective_date],
        //                     ])
        //                     ->get()

        //                     // group item according to reccuring date
        //                     ->groupBy(function ($order) use ($incentive) {
        //                         $orderDate = Carbon::parse($order->created_at);
        //                         $startOfMonth = Carbon::create($orderDate->year, $orderDate->month, $incentive->recurring_on);
        //                         $endOfMonth = $startOfMonth->copy()->addMonth()->subDay();
                        
        //                         if ($orderDate->between($startOfMonth, $endOfMonth)) {
        //                             return $startOfMonth->format('F Y'); 
        //                         } else {
        //                             return $startOfMonth->subMonth()->format('F Y');
        //                         }
        //                     })

        //                     //start matching respective orders to their waiter
        //                     ->map(function ($dateGroup, $month) use ($incentive, $id) {
        //                         $data = $dateGroup->groupBy('user_id')->map(function ($userGroup) use ($incentive, $id, $month) {
        //                             $totalSales = $userGroup->sum('amount');
                                    
        //                             if ($totalSales > $incentive->monthly_sale) {
        //                                 // calculate incentive amount
        //                                 if ($incentive->type === 'fixed') {
        //                                     $incentiveAmt = $incentive->rate;
        //                                 } else {
        //                                     $incentiveAmt = $totalSales * $incentive->rate;
        //                                 }
                                
        //                                 // status
        //                                 $status = $incentive->incentiveEmployees
        //                                             ->where('incentive_id', $id)
        //                                             ->where('user_id', optional($userGroup->first()->handledBy)->id)
        //                                             ->filter(function ($employeeIncentive) use ($month) {
        //                                                 return Carbon::parse($employeeIncentive->created_at)->format('F Y') === $month;
        //                                             })
        //                                             ->first() 
        //                                             ->status ?? 'not found';

        //                                 $empIncentiveId = $incentive->incentiveEmployees
        //                                                 ->where('incentive_id', $id)
        //                                                 ->where('user_id', optional($userGroup->first()->handledBy)->id)
        //                                                 ->filter(function ($employeeIncentive) use ($month) {
        //                                                     return Carbon::parse($employeeIncentive->created_at)->format('F Y') === $month;
        //                                                 })
        //                                                 ->first() 
        //                                                 ->id ?? null;
                                
        //                                 $result = [
        //                                     'id' => $empIncentiveId,
        //                                     'name' => $userGroup->first()->handledBy->full_name ?? null, // achiever
        //                                     'total_sales' => $totalSales, // sales
        //                                     'monthly_sale' => $incentive->monthly_sale,
        //                                     'incentive' => round($incentiveAmt, 2), // earned
        //                                     'status' => $status, // status
        //                                     'image' => $userGroup->first()->handledBy ? $userGroup->first()->handledBy->getFirstMediaUrl('user') : null,
        //                                 ];

        //                                 return in_array(null, $result, true) ? null : $result;
        //                             }
        //                             return null;

        //                         })->filter()->values();

        //                         if ($data->isNotEmpty()) {
        //                             return [
        //                                 'month' => $month, 
        //                                 'data' => $data->toArray(), 
        //                             ];
        //                         }
        //                         return null;
        //                     })
        //                     ->filter()
        //                     ->values()
        //                     ->toArray();

                            // dd($sales);

        $employeeIncentives = $incentive->earnedIncentives()
                                        ->with('waiter:id,full_name')
                                        ->select([
                                            'id', 'user_id', 'amount','sales_target', 
                                            'rate', 'status', 'period_start'
                                        ])
                                        ->orderBy('period_start')
                                        ->get()
                                        ->map(fn ($empIncentive) => [
                                            'id' => $empIncentive->id,
                                            'name' => $empIncentive->waiter?->full_name,
                                            'total_sales' => $empIncentive->amount,
                                            'monthly_sale' => number_format($empIncentive->sales_target, 2),
                                            'incentive' => round($empIncentive->rate, 2),
                                            'status' => $empIncentive->status,
                                            'image' => $empIncentive->waiter?->getFirstMediaUrl('user'),
                                            'period_start' => $empIncentive->period_start
                                        ])
                                        ->groupBy(fn($item) => Carbon::parse($item['period_start'])->format('F Y'))
                                        ->map(fn ($group, $month) => [
                                            'month' => $month,
                                            'data' => $group->map(fn($item) => collect($item)
                                                ->except('period_start')
                                                ->toArray()
                                            )->values()->all()
                                        ])
                                        ->values()
                                        ->all();
                                            
        $waiterName = $entitled->map(function($entitleds) {
                                    return $entitleds->waiters->map(function($waiter) {
                                        return [
                                            'id' => $waiter->id,
                                            'name' => $waiter->full_name,
                                            'image' => $waiter->getFirstMediaUrl('user'),
                                        ];
                                    });
                                })
                                ->flatten(1)
                                ->unique('name')
                                ->sortByDesc('name');

        return Inertia::render('Configuration/IncentiveProgram/Partials/IncentiveCommissionDetail', [
            'details' => $employeeIncentives,
            'achievementDetails' => $incentive,
            'waiterName' => $waiterName,
        ]);
    }

    public function getIncentDetail(string $id)
    {
        $incentive = ConfigIncentive::with('incentiveEmployees')->find($id);

        // get related waiters
        // $entitled = ConfigIncentiveEmployee::where('incentive_id', $id)
        //                                     ->orderBy('created_at')
        //                                     ->where('created_at', '>=', $incentive->effective_date)
        //                                     ->with([
        //                                         'configIncentive',
        //                                         'waiters:id,full_name',
        //                                         'waiters.itemSales' => function($query) use ($incentive) {
        //                                             $query->where([
        //                                                 // ['status', 'Served'],
        //                                                 // ['type', 'Normal'],
        //                                                 ['created_at', '>=', $incentive->effective_date],
        //                                             ]);
        //                                         }
        //                                     ])
        //                                     ->get();

        // $sales = OrderItem::with(['handledBy' => fn($query) => $query->where('position', 'waiter')])
        //                     ->where([
        //                         ['status', 'Served'],
        //                         ['type', 'Normal'],
        //                         ['created_at', '>=', $incentive->effective_date],
        //                     ])
        //                     ->get()

        //                     // group item according to reccuring date
        //                     ->groupBy(function ($order) use ($incentive) {
        //                         $orderDate = Carbon::parse($order->created_at);
        //                         $startOfMonth = Carbon::create($orderDate->year, $orderDate->month, $incentive->recurring_on);
        //                         $endOfMonth = $startOfMonth->copy()->addMonth()->subDay();
                        
        //                         if ($orderDate->between($startOfMonth, $endOfMonth)) {
        //                             return $startOfMonth->format('F Y'); 
        //                         } else {
        //                             return $startOfMonth->subMonth()->format('F Y');
        //                         }
        //                     })

        //                     //start matching respective orders to their waiter
        //                     ->map(function ($dateGroup, $month) use ($incentive, $id) {
        //                         $data = $dateGroup->groupBy('user_id')->map(function ($userGroup) use ($incentive, $id, $month) {
        //                             $totalSales = $userGroup->sum('amount');
                                
        //                             if ($totalSales > $incentive->monthly_sale) {
        //                                 // calculate incentive amount
        //                                 if ($incentive->type === 'fixed') {
        //                                     $incentiveAmt = $incentive->rate;
        //                                 } else {
        //                                     $incentiveAmt = $totalSales * $incentive->rate;
        //                                 }
                                
        //                                 // status
        //                                 $status = $incentive->incentiveEmployees
        //                                             ->where('incentive_id', $id)
        //                                             ->where('user_id', optional($userGroup->first()->handledBy)->id)
        //                                             ->filter(function ($employeeIncentive) use ($month) {
        //                                                 return Carbon::parse($employeeIncentive->created_at)->format('F Y') === $month;
        //                                             })
        //                                             ->first() 
        //                                             ->status ?? 'not found';

        //                                 $empIncentiveId = $incentive->incentiveEmployees
        //                                                 ->where('incentive_id', $id)
        //                                                 ->where('user_id', optional($userGroup->first()->handledBy)->id)
        //                                                 ->filter(function ($employeeIncentive) use ($month) {
        //                                                     return Carbon::parse($employeeIncentive->created_at)->format('F Y') === $month;
        //                                                 })
        //                                                 ->first() 
        //                                                 ->id ?? null;
                                
        //                                 $result = [
        //                                     'id' => $empIncentiveId,
        //                                     'name' => $userGroup->first()->handledBy->full_name ?? null, // achiever
        //                                     'total_sales' => $totalSales, // sales
        //                                     'monthly_sale' => $incentive->monthly_sale,
        //                                     'incentive' => round($incentiveAmt, 2), // earned
        //                                     'status' => $status, // status
        //                                 ];

        //                                 return in_array(null, $result, true) ? null : $result;
        //                             }
        //                             return null;

        //                         })->filter()->values();

        //                         if ($data->isNotEmpty()) {
        //                             return [
        //                                 'month' => $month, 
        //                                 'data' => $data->toArray(), 
        //                             ];
        //                         }
        //                         return null;
        //                     })
        //                     ->filter()
        //                     ->values()
        //                     ->toArray();

        // $waiterName = $entitled->map(function($entitleds) {
        //                             return $entitleds->waiters->map(function($waiter) {
        //                                 return [
        //                                     'id' => $waiter->id,
        //                                     'name' => $waiter->full_name,
        //                                 ];
        //                             });
        //                         })
        //                         ->flatten(1)
        //                         ->unique('name')
        //                         ->sortByDesc('name');

        return response()->json(data: [
            'achievementDetails' => $incentive,
            // 'incentiveProg' => $sales,
            // 'waiters' => $waiterName,
        ]);
    }

    public function deleteEntitled (String $achievement, String $id)
    {
        ConfigIncentiveEmployee::where('user_id', $id)
                                ->where('incentive_id', $achievement)
                                ->delete();
                                
    }

    public function updateStatus(Request $request, string $id)
    {
        $status = $request->input('selectedStatus');

        EmployeeIncentive::find($id)->update(['status' => $status]);
    
        return redirect()->back();
    }

    public function updateIncentiveRecurringDay(Request $request)
    {
        $newRecurringDay = $request->recurring_on;
        // dd($this->incentiveRecurringDay);
        $this->incentiveRecurringDay->update(['value' => $newRecurringDay]);

        $incentives = ConfigIncentive::get();
        $incentives->each(fn ($incentive) => $incentive->update(['recurring_on' => $newRecurringDay]));

        return response()->json($newRecurringDay);
    }
}
