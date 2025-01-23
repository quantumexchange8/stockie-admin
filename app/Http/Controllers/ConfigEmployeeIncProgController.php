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
        $allWaiters = User::where('position', 'waiter')
                            ->orderBy('full_name')
                            ->get(['id', 'full_name']);

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
                                                'entitled' => $incentive->incentiveEmployees->where('status', 'Active')->flatMap(function ($employee) {
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
        $validatedData = $request->validate([
            'comm_type' => 'required|string',
            'rate' => 'required|string',
            'effective_date' => 'required|date',
            'monthly_sale' => 'required',
            'entitled' => 'required',
        ], [
            'required' => 'This field is required.',
            'string' => 'This field must be a valid string.',
        ]);

        $rate = $validatedData['comm_type']['value'] == 'fixed' ? $validatedData['rate'] : $validatedData['rate'] / 100;

        $incentive = ConfigIncentive::create([
            'type' => $validatedData['comm_type']['value'],
            'rate' => $rate,
            'effective_date' => Carbon::parse($validatedData['effective_date'])->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s'),
            'recurring_on' => $this->incentiveRecurringDay['value'],
            'monthly_sale' => round($validatedData['monthly_sale'], 2),
        ]);

        foreach ($validatedData['entitled'] as $entitledWaiters) {
            ConfigIncentiveEmployee::create([
                'incentive_id' => $incentive->id,
                'user_id' => $entitledWaiters,
                'status' => 'Active',
            ]);
        }
        return redirect()->back();
    }

    public function deleteAchievement(String $id)
    {
        ConfigIncentiveEmployee::where('incentive_id', $id)->delete();
        ConfigIncentive::find($id)->delete();

        return redirect()->route('configurations');
    }

    public function editAchievement(Request $request)
    {
        $validatedData = $request->validate([
            'comm_type' => 'required|string',
            'rate' => 'required|string',
            'effective_date' => 'required|date',
            'monthly_sale' => 'required',
        ], [
            'required' => 'This field is required.',
            'string' => 'This field must be a valid string.',
        ]);
        
        ConfigIncentive::find($request->id)->update([
            'type' => $validatedData['comm_type']['value'],
            'rate' => $validatedData['comm_type']['value'] === 'fixed' ? $validatedData['rate'] : round($validatedData['rate'] / 100, 2),
            'effective_date' => $validatedData['effective_date'],
            'recurring_on' => $this->incentiveRecurringDay['value'],
            'monthly_sale' => (float)$validatedData['monthly_sale'],
        ]);

        return redirect()->back();
    }

    public function incentCommDetail(String $id)
    {
        $incentive = ConfigIncentive::with('incentiveEmployees')->find($id);

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

        $waiterName = User::where('position', 'waiter')
                        ->wherehas('configIncentEmployee', fn ($query) => $query->where([['incentive_id', $id], ['status', 'Active']]))
                        ->get(['id', 'full_name'])
                        ->map(function ($waiter) {
                            $waiter->name = $waiter->full_name;
                            $waiter->image = $waiter->getFirstMediaUrl('user');
                            return $waiter;
                        });

        return Inertia::render('Configuration/IncentiveProgram/Partials/IncentiveCommissionDetail', [
            'details' => $employeeIncentives,
            'achievementDetails' => $incentive,
            'waiterName' => $waiterName,
        ]);
    }

    public function getIncentDetail(string $id)
    {
        $incentive = ConfigIncentive::with('incentiveEmployees')->find($id);

        return response()->json(data: [
            'achievementDetails' => $incentive,
        ]);
    }

    public function deleteEntitled (Request $request, string $id)
    {
        $entitlement = ConfigIncentiveEmployee::where([
                                                    ['user_id', $request->entitled_employee_id],
                                                    ['incentive_id', $id]
                                                ])
                                                ->first();
        $targetEntitled = User::where('id', $request->entitled_employee_id)->first();

        $entitlement->update(['status' => 'Inactive']);

        activity()->useLog('delete-entitled')
                    ->performedOn($entitlement)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'entitled_name' => $targetEntitled->full_name,
                    ])
                    ->log("$targetEntitled->full_name is deleted from entitled incentive listing.");

        $entitled = User::where('position', 'waiter')
                        ->wherehas('configIncentEmployee', fn ($query) => $query->where([['incentive_id', $id], ['status', 'Active']]))
                        ->get(['id', 'full_name'])
                        ->map(function ($waiter) {
                            $waiter->name = $waiter->full_name;
                            $waiter->image = $waiter->getFirstMediaUrl('user');
                            unset($waiter->full_name);

                            return $waiter;
                        });

        return response()->json($entitled);                                
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
        $this->incentiveRecurringDay->update(['value' => $newRecurringDay]);

        $incentives = ConfigIncentive::get();
        $incentives->each(fn ($incentive) => $incentive->update(['recurring_on' => $newRecurringDay]));

        return response()->json($newRecurringDay);
    }

    public function getAllWaiters(string $id)
    {
        $waiters = User::where('position', 'waiter')
                        ->whereDoesntHave('configIncentEmployee', fn ($query) => $query->where([['incentive_id', $id], ['status', 'Active']]))
                        ->get(['id', 'full_name'])
                        ->map(function ($waiter) {
                            $waiter->image = $waiter->getFirstMediaUrl('user');
                            return $waiter;
                        });


        return response()->json($waiters);
    }

    public function addEntitledEmployees(Request $request, string $id) 
    {
        foreach ($request->entitledEmployees as $employee) {
            ConfigIncentiveEmployee::updateOrCreate(
                [ 'incentive_id' => $id, 'user_id' => $employee['id'] ],
                [ 'status' => 'Active' ]
            );
        };

        $waiters = User::where('position', 'waiter')
                        ->wherehas('configIncentEmployee', fn ($query) => $query->where([['incentive_id', $id], ['status', 'Active']]))
                        ->get(['id', 'full_name'])
                        ->map(function ($waiter) {
                            $waiter->name = $waiter->full_name;
                            $waiter->image = $waiter->getFirstMediaUrl('user');
                            unset($waiter->full_name);

                            return $waiter;
                        });

        return response()->json($waiters);
    }
}
