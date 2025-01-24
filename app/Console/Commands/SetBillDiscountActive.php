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
    
        BillDiscount::where(function($query) use ($today) {
            $query->whereDate('discount_from', '<=', $today)
                ->whereDate('discount_to', '>=', $today);
        })->get()
            ->each(function($discount) use ($today) {
            switch ($discount->available_on) {
                case 'everyday':
                    $discount->status = 'active';
                    break;
                case 'weekday':
                    $discount->status = $today->isWeekday() ? 'active' : 'inactive';
                    break;
                case 'weekend':
                    $discount->status = $today->isWeekend() ? 'active' : 'inactive';
                    break;
            }
            $discount->save();
        });

        $this->info("Set bill discount successfully.");

    }
}
