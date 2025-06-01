<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\PointHistory;
use Illuminate\Console\Command;

class ExpireCustomerPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:expire-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all customer point histories for expiration and expire them by deducting customer points';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $usableHistories = PointHistory::where(function ($query) {
                                            $query->where(function ($subQuery) {
                                                    $subQuery->where([
                                                                ['type', 'Earned'],
                                                                ['expire_balance', '>', 0],
                                                            ])
                                                            ->whereNotNull('expired_at')
                                                            ->whereDate('expired_at', now());
                                                })
                                                ->orWhere(function ($subQuery) {
                                                    $subQuery->where([
                                                                ['type', 'Adjusted'],
                                                                ['expire_balance', '>', 0]
                                                            ])
                                                            ->whereNotNull('expired_at')
                                                            ->whereColumn('new_balance', '>', 'old_balance')
                                                            ->whereDate('expired_at', now());
                                                });
                                        })
                                        ->orderBy('expired_at', 'asc') // earliest expiry first
                                        ->get();

        foreach ($usableHistories as $record) { 
            $customer = Customer::where('id', $record->customer_id)->first();

            if ($customer) {
                $customer->decrement('point', $record->expire_balance);
    
                $record->expire_balance = 0;
                $record->save();
    
                PointHistory::create([
                    'product_id' => $record->product_id,
                    'payment_id' => $record->payment_id,
                    'type' => 'Expired',
                    'point_type' => $record->point_type, 
                    'qty' => $record->qty,
                    'amount' => $record->amount,
                    'old_balance' => $record->old_balance,
                    'new_balance' => $record->new_balance,
                    'expire_balance' => 0,
                    'expired_at' => $record->expire_at,
                    'customer_id' => $record->customer_id,
                    'handled_by' => $record->handled_by,
                    'redemption_date' => $record->redemption_date
                ]);
            }
        } 

        $expiredRecordsCount = $usableHistories->count();
        
        $this->info("Expired $expiredRecordsCount point histories that have reached their expiration date.");
    }
}
