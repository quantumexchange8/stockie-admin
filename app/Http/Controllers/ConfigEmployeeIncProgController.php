<?php

namespace App\Http\Controllers;

use App\Models\ConfigIncentive;
use App\Models\ConfigIncentiveEmployee;
use App\Models\Order;
use App\Models\Waiter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Log;

class ConfigEmployeeIncProgController extends Controller
{
    public function index(Request $request)
    {
        $allWaiters = Waiter::select('id', 'name')->get();
        $incentiveProg = ConfigIncentive::with('incentiveEmployees.waiters')
                                        ->get()
                                        ->map(function ($incentive) {
                                            return [
                                                'id' => $incentive->id,
                                                'type' => $incentive->type,
                                                'rate' => $incentive->rate,
                                                'effective_date' => $incentive->effective_date,
                                                'recrurring_on' => $incentive->recrurring_on,
                                                'monthly_sale' => $incentive->monthly_sale,
                                                'entitled' => $incentive->incentiveEmployees->flatMap(function ($employee) {
                                                    return $employee->waiters->map(function ($waiter) {
                                                        return [
                                                            'id' => $waiter->id ?? null,
                                                            'name' => $waiter->name ?? null,
                                                        ];
                                                    });
                                                })->unique('id'),
                                            ];
                                        });

        return response()->json([
            'incentiveProg' => $incentiveProg,
            'waiters' => $allWaiters,
        ]);


    }

    public function addAchievement(Request $request)
    {
        if($request->comm_type['value'] == 'fixed')
        {
            $rate = $request->rate;
        } else {
            $rate = $request->rate / 100;
        }

        $incentive = ConfigIncentive::create([
            'type' => $request->comm_type['value'],
            'rate' => $rate,
            'effective_date' => Carbon::parse($request->effective_date)->startOfDay()->format('Y-m-d H:i:s'),
            'recrurring_on' => $request->recurring_on['value'],
            'monthly_sale' => (float)$request->monthly_sale,
        ]);

        foreach ($request->entitled as $entitledWaiters)
        {
            ConfigIncentiveEmployee::create([
                'incentive_id' => $incentive->id,
                'user_id' => $entitledWaiters,
                'status' => 'Pending',
            ]);
        }
        // return redirect()->back()->with(['message' => $message]);
    }

    public function deleteAchievement(String $id)
    {
        ConfigIncentive::find($id)->delete();
        ConfigIncentiveEmployee::where('incentive_id', $id)->delete();

        $message = [
            'severity' => 'success',
            'summary' => 'Achievement has been deleted.'
        ];

        return redirect()->route('configurations')->with(['message' => $message]);
    }

    public function editAchievement(Request $request)
    {
        if($request->comm_type['value'] == 'fixed')
        {
            $rate = $request->rate;
        } else {
            $rate = $request->rate / 100;
        }

        ConfigIncentive::find($request->id)->update([
            'type' => $request->comm_type['value'],
            'rate' => $rate,
            'effective_date' => Carbon::parse($request->effective_date)->startOfDay()->format('Y-m-d H:i:s'),
            'recrurring_on' => $request->recurring_on['value'],
            'monthly_sale' => (float)$request->monthly_sale,
        ]);

    }

    public function incentCommDetail(String $id)
    {
        $entitled = ConfigIncentiveEmployee::where('incentive_id', $id)
                                            ->with(['configIncentive', 'waiters.orders'])
                                            ->get();

                                            // dd($entitled);
        $waiterName = $entitled->map(function($entitleds){
            return [
                'waiters' => $entitleds->waiters->flatMap(function($waiter){
                    return [
                        'id' => $waiter->id,
                        'name' => $waiter->name,
                    ];
                })
            ];
        })->unique();

        $incentCommDetail = ConfigIncentive::find($id);
        $incentEffective = $incentCommDetail->effective_date;
        $recurringDay = $incentCommDetail->recrurring_on;

        $waiterDetails = [];

        $entitled->each(function ($entitled) use ($incentEffective, &$waiterDetails, &$incentCommDetail, &$recurringDay) {
            $entitled->waiters->each(function ($waiter) use ($incentEffective, &$waiterDetails, &$entitled, &$incentCommDetail, &$recurringDay) {
                $sales = $waiter->orders()
                    ->where('created_at', '>', $incentEffective)
                    ->where('status', 'Order Completed')
                    ->get()
                    ->groupBy(function ($order) use ($recurringDay, &$entitled) {
                        $orderDate = Carbon::parse($order->created_at);
                        $startOfMonth = Carbon::create($orderDate->year, $orderDate->month, $recurringDay);
                        $endOfMonth = $startOfMonth->copy()->addMonth()->subDay();
        
                        if ($orderDate->between($startOfMonth, $endOfMonth)) {
                            return $startOfMonth->format('F Y');
                        } else {
                            return $startOfMonth->subMonth()->format('F Y');
                        }
        
                    })
                    ->map(function ($group) use ($waiter, &$entitled) {
                        $month = $group->first()->created_at->format('F');
                        $year = $group->first()->created_at->format('Y');
        
                        $status = null;
        
                        if ($entitled->user_id == $waiter->id) {
                            $entitledMonth = $entitled->created_at->format('F Y');
                            $salesMonth = $month . ' ' . $year;
        
                            Log::info('Comparing entitled month: ' . $entitledMonth . ' with sales month: ' . $salesMonth);
        
                            if ($entitledMonth == $salesMonth) {
                                Log::info('Match found for entitled with user_id ' . $entitled->user_id . ' in ' . $salesMonth);
                                $status = $entitled->status;
                            } else {
                                Log::info('No match for entitled in ' . $salesMonth);
                            }
                        }
        
                        return [
                            'total_sales' => $group->sum('total_amount'),
                            'month' => $month,
                            'year' => $year,
                            'status' => $status // Ensure the status is passed along
                        ];
                    });
        
                foreach ($sales as $sale) {
                    $year = $sale['year'];
                    $month = $sale['month'];
                    $total_sales = $sale['total_sales'];
                    $status = $sale['status'];
        
                    if (!isset($waiterDetails[$year][$month][$waiter->id])) {
                        $waiterDetails[$year][$month][$waiter->id] = []; // Ensure unique waiter entries
                    }
        
                    if (!collect($waiterDetails[$year][$month])->contains('name', $waiter->name) && $total_sales > $incentCommDetail->monthly_sale) {
        
                        $earned = $incentCommDetail->type === 'fixed'
                            ? $incentCommDetail->rate
                            : $total_sales * $incentCommDetail->rate;
        
                        $waiterDetails[$year][$month][$waiter->id] = [
                            'name' => $waiter->name,
                            'total_sales' => number_format($total_sales, 2),
                            'earned' => (int) $earned,
                            'status' => $status,
                        ];
                    }
                }
        
                Log::info('Current waiter details:', $waiterDetails);
            });
        });
        
        // dd(array_values($waiterDetails));
        
        return Inertia::render('Configuration/IncentiveProgram/Partials/IncentiveCommissionDetail', [
            'details' => $waiterDetails,
            'achievementDetails' => $incentCommDetail,
            'waiterName' => $waiterName,
        ]);
    }

    public function deleteEntitled (String $achievement, String $id)
    {
        ConfigIncentiveEmployee::where('user_id', $id)
                                ->where('incentive_id', $achievement)
                                ->delete();
                                
    }
}
