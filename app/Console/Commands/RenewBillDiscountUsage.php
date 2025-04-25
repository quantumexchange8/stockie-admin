<?php

namespace App\Console\Commands;

use App\Models\BillDiscount;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RenewBillDiscountUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configuration:new-bill-discount-usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renew the customer usage and/or total usage based on the selected renew schedule';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today('Asia/Kuala_Lumpur');
        $isMonday = $today->dayOfWeek === 1;
        $isFirstDayOfMonth = $today->day === 1;
    
        // Only get discounts that need renewal
        $billDiscounts = BillDiscount::where(function($query) {
                                            $query->whereNotNull('customer_usage_renew')
                                                ->orWhereNotNull('total_usage_renew');
                                        })
                                        ->with('billDiscountUsages')
                                        ->get();
    
        $billDiscounts->each(function ($discount) use ($isMonday, $isFirstDayOfMonth) {
            $renewCU = $discount->customer_usage_renew;
            $renewTU = $discount->total_usage_renew;
            
            $shouldResetCU = match($renewCU) {
                'daily' => true,
                'weekly' => $isMonday,
                'monthly' => $isFirstDayOfMonth,
                default => false
            };
            
            $shouldResetTU = match($renewTU) {
                'daily' => true,
                'weekly' => $isMonday,
                'monthly' => $isFirstDayOfMonth,
                default => false
            };
    
            if ($shouldResetCU) {
                $discount->billDiscountUsages()->update(['customer_usage' => 0]);
            }
    
            if ($shouldResetTU) {
                $discount->update(['remaining_usage' => $discount->total_usage]);
            }
        });

        $this->info("Set bill discount successfully.");
    }
}
