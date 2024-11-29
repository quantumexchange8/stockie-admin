<?php

namespace App\Console\Commands;

use App\Models\KeepHistory;
use App\Models\KeepItem;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckAndExpireKeepItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keepitems:check-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all customer keep items for expiration and update their status if expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $keepItems = KeepItem::where('status', '!=', 'Expired')->whereNotNull('expired_to')->get(); 
        foreach ($keepItems as $item) { 
            if (now()->greaterThanOrEqualTo(Carbon::parse($item->expired_to)->endOfDay())) { 
                $item->update(['status' => 'Expired']); 
                
                KeepHistory::create([
                    'keep_item_id' => $item->id,
                    'qty' => $item->qty,
                    'cm' => $item->cm,
                    'keep_date' => $item->created_at,
                    'status' => 'Expired',
                ]);
            } 
        } 
        $this->info('Checked and updated expired keep items.');
    }
}
