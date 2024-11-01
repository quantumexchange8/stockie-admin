<?php

namespace App\Console\Commands;

use App\Models\ConfigDiscount;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateProductDiscount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configuration:update-product-discount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update products discount based on the discount period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $todayDate = Carbon::today()->endOfDay();

        $inactiveDiscounts = ConfigDiscount::with('discountItems.product')
                                            ->where('discount_to', '<', $todayDate)
                                            ->orWhere('discount_from', '>', $todayDate)
                                            ->get();

        foreach ($inactiveDiscounts as $discount) {
            foreach ($discount->discountItems as $discountItem) {
                $discountItem->product->update(['discount_id' => null]);
            }
        }

        $activeDiscounts = ConfigDiscount::with('discountItems.product')
                                            ->where('discount_from', '<=', $todayDate)
                                            ->where('discount_to', '>=', $todayDate)
                                            ->get();

        foreach ($activeDiscounts as $discount) {
            foreach ($discount->discountItems as $discountItem) {
                $discountItem->product->update(['discount_id' => $discount->id]);
            }
        }
    }

}
