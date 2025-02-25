<?php

namespace App\Console\Commands;

use App\Models\KeepHistory;
use App\Models\KeepItem;
use App\Models\StockHistory;
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
        $keepItems = KeepItem::with('orderItemSubitem.productItem.inventoryItem')
                                ->where('status', '!=', 'Expired')
                                ->whereNotNull('expired_to')
                                ->get(); 

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

                $inventoryItem = $item->orderItemSubitem->productItem->inventoryItem;
                if ($item->qty > $item->cm) {
                    $expiredKeepItem = $item->qty;
                    $oldStock = $inventoryItem->stock_qty;
                    $newStock = $oldStock + $expiredKeepItem;
                    $newKeptBalance = $inventoryItem->current_kept_amt - $item->qty;

                    $inventoryItem->update([
                        'stock_qty' => $newStock, 
                        'current_kept_amt' => $newKeptBalance, 
                    ]);

                    StockHistory::create([
                        'inventory_id' => $inventoryItem->inventory_id,
                        'inventory_item' => $inventoryItem->item_name,
                        'old_stock' => $oldStock,
                        'in' => $expiredKeepItem,
                        'out' => 0,
                        'current_stock' => $newStock,
                        'kept_balance' => $newKeptBalance
                    ]);
                }
            } 
        } 

        $this->info('Checked and updated expired keep items.');
    }
}
