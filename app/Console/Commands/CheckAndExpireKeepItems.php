<?php

namespace App\Console\Commands;

use App\Models\KeepHistory;
use App\Models\KeepItem;
use App\Models\StockHistory;
use App\Models\User;
use App\Notifications\InventoryOutOfStock;
use App\Notifications\InventoryRunningOutOfStock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

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
        // $keepItems = KeepItem::with('orderItemSubitem.productItem.inventoryItem')
        //                         ->where('status', '!=', 'Expired')
        //                         ->whereNotNull('expired_to')
        //                         ->get(); 

        // foreach ($keepItems as $item) { 
        //     if (now()->greaterThanOrEqualTo(Carbon::parse($item->expired_to)->endOfDay())) { 
        //         $item->update(['status' => 'Expired']); 

        //         activity()->useLog('expire-kept-item')
        //                     ->performedOn($item)
        //                     ->event('updated')
        //                     ->withProperties([
        //                         'edited_by' => auth()->user()->full_name,
        //                         'image' => auth()->user()->getFirstMediaUrl('user'),
        //                         'item_name' => $item->orderItemSubitem->productItem->inventoryItem->item_name,
        //                     ])
        //                     ->log(":properties.item_name is expired.");
                
        //         KeepHistory::create([
        //             'keep_item_id' => $item->id,
        //             'qty' => $item->qty,
        //             'cm' => $item->cm,
        //             'keep_date' => $item->created_at,
        //             'user_id' => auth()->user()->id,
        //             'kept_from_table' => $item->kept_from_table,
        //             'status' => 'Expired',
        //         ]);

        //         $inventoryItem = $item->orderItemSubitem->productItem->inventoryItem;
        //         if ($item->qty > $item->cm) {
        //             $expiredKeepItem = $item->qty;
        //             $oldStock = $inventoryItem->stock_qty;

        //             $newStock = $oldStock + $expiredKeepItem;
        //             $newKeptBalance = $inventoryItem->current_kept_amt - $expiredKeepItem;
        //             $newTotalKept = $inventoryItem->total_kept - $expiredKeepItem;

        //             $newStatus = match(true) {
        //                 $newStock == 0 => 'Out of stock',
        //                 $newStock <= $inventoryItem->low_stock_qty => 'Low in stock',
        //                 default => 'In stock'
        //             };

        //             $inventoryItem->update([
        //                 'stock_qty' => $newStock, 
        //                 'status' => $newStatus,
        //                 'current_kept_amt' => $newKeptBalance, 
        //                 'total_kept' => $newTotalKept, 
        //             ]);

        //             StockHistory::create([
        //                 'inventory_id' => $inventoryItem->inventory_id,
        //                 'inventory_item' => $inventoryItem->item_name,
        //                 'old_stock' => $oldStock,
        //                 'in' => $expiredKeepItem,
        //                 'out' => 0,
        //                 'current_stock' => $newStock,
        //                 'kept_balance' => $newKeptBalance
        //             ]);

        //             if($newStatus === 'Out of stock'){
        //                 Notification::send(User::all(), new InventoryOutOfStock($inventoryItem->item_name, $inventoryItem->id));
        //             };

        //             if($newStatus === 'Low in stock'){
        //                 Notification::send(User::all(), new InventoryRunningOutOfStock($inventoryItem->item_name, $inventoryItem->id));
        //             }
        //         }
        //     } 
        // } 

        // $this->info('Checked and updated expired keep items.');
    }
}
