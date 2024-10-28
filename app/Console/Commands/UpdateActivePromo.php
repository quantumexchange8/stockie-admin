<?php

namespace App\Console\Commands;

use App\Models\ConfigPromotion;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateActivePromo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configuration:update-active-promo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect everyday at midnight to update and show the current ongoing promotions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $todayDate = Carbon::today()->endOfDay();
        // deactivate promotions that are not ongoing
        ConfigPromotion::where('promotion_to', '<', $todayDate)
                        ->orWhere('promotion_from', '>', $todayDate)
                        ->update(['status' => 'Inactive']);

        // activate promotions that are currently ongoing
        ConfigPromotion::where('promotion_from', '<=', $todayDate)
                        ->where('promotion_to', '>=', $todayDate)
                        ->update(['status' => 'Active']);
        
        $this->info('Promotion event refreshed');
        return;
    }
}
