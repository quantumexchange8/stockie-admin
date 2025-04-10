<?php

namespace App\Console\Commands;

use App\Models\BillDiscount;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SetBillDiscountActive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configurations:set-bill-discount-active';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Control bill discount status based on their available timestamp.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today('Asia/Kuala_Lumpur');
        $billDiscounts = BillDiscount::where('status', 'active')->get();
                    
        $billDiscounts->each(function($discount) use ($today) {
            $discount->status = match ($discount->available_on) {
                'everyday' => 'active',
                'weekday' => $today->isWeekday() ? 'active' : 'inactive',
                'weekend' => $today->isWeekend() ? 'active' : 'inactive',
            };
            $discount->save();
        });

        $this->info("Set bill discount successfully.");
    }
}
