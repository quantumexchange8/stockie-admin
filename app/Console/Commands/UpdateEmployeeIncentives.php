<?php

namespace App\Console\Commands;

use App\Models\ConfigIncentive;
use App\Models\EmployeeIncentive;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateEmployeeIncentives extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configuration:update-employee-incentives';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Checks on each employee's total sales based on the recurring day and effective day against the incentives' threshold, before add them to their highest earned incentive";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Log the starting time of the command
        Log::debug('Start time: ' . now()->toDateTimeString());

        // Get the recurring day setting
        $recurringDay = (int) Setting::where('name', 'Recurring Day')->first()->value;
        
        // Calculate date range
        $dates = $this->calculateDateRange($recurringDay);
        
        if ($dates) {
            $incentives = ConfigIncentive::get();
            $waiters = User::where('position', 'waiter')->get();
            
            foreach ($waiters as $waiter) {
                // Calculate total sales for the period
                $totalSales = $waiter->itemSales()
                                        ->whereHas('order', fn ($query) => $query->whereBetween('created_at', [$dates['start'], $dates['end']]))
                                        ->sum('amount');
                
                // Find the highest achievable incentive
                $achievedIncentive = $this->findHighestAchievableIncentive($incentives, $totalSales, $recurringDay);
                
                if ($achievedIncentive) {
                    // Create employee incentive record
                    EmployeeIncentive::create([
                        'user_id' => $waiter->id,
                        'incentive_id' => $achievedIncentive->id,
                        'type' => $achievedIncentive->type,
                        'rate' => $achievedIncentive->rate,
                        'amount' => $totalSales,
                        'sales_target' => $achievedIncentive->monthly_sale,
                        'recurring_on' => $recurringDay,
                        'effective_date' => $achievedIncentive->effective_date,
                        'period_start' => $dates['start'],
                        'period_end' => $dates['end'],
                        'status' => 'Pending',
                    ]);
                }
            }
        }

        // Log the ending time of the command
        Log::debug('End time: ' . now()->toDateTimeString());
    }
    
    private function calculateDateRange($recurringDay)
    {
        // If today is the recurring day, calculate for the period from last month based on the recurring day
        switch (now()->day) {
            case $recurringDay:
                $cutOff = true;
                $start = now()->clone()->subMonthWithoutOverflow();
                $end = now()->clone()->subDay(); // Yesterday
                break;
            
            default:
                $cutOff = false;
                break;
        }
        
        return $cutOff 
                ?   [
                        'start' => $start->startOfDay()->toDateTimeString(),
                        'end' => $end->endOfDay()->toDateTimeString(),
                    ]
                :   null;
    }
    
    private function findHighestAchievableIncentive($incentives, $totalSales, $recurringDay)
    {
        // Filter through the incentives to get the highest achievable incentive for the waiter based on the total sales
        // The incentives must only be taken account during the next month from the effective date
        return $incentives->filter(function ($incentive) use ($totalSales, $recurringDay) {
                                $effectiveDate = new Carbon($incentive->effective_date);
                                $incentiveStartingDate = $effectiveDate->addMonthWithoutOverflow();
                                $recurringDayStart = now()->setDateTime($incentiveStartingDate->year, $incentiveStartingDate->month, $recurringDay, 0, 0, 0);

                                return $totalSales >= $incentive->monthly_sale
                                        && now() >= $recurringDayStart;
                            })
                            ->sortByDesc('monthly_sale')
                            ->first();
    }
}
