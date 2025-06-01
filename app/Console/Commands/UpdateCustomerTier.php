<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\PointHistory;
use App\Models\Ranking;
use App\Models\Setting;
use Illuminate\Console\Command;

class UpdateCustomerTier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:update-tier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Check each customer's total spending by the end of the year and update their ranks accordingly.";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all active customers
        $allCustomers = Customer::where('status', '!=', 'void')->get();
        
        // Get the predefined number of days for the point to expire
        $pointExpirationDays = Setting::where([
                                            ['name', 'Point Expiration'],
                                            ['type', 'expiration']
                                        ])
                                        ->first(['id', 'value']);

        // Predicted point expiration date
        $pointExpiredDate = now()->addDays((int)$pointExpirationDays->value);
        
        foreach ($allCustomers as $customer) {
            $currentRanking = $customer->ranking;
            $currentPoint = $customer->point;
            $totalSpending = $customer->total_spending;

            // Get the highest rank (by min amount) achieved
            $newRankingDetails = Ranking::where('min_amount', '<=', $totalSpending)
                                        ->select('id', 'name')
                                        ->orderBy('min_amount', 'desc')
                                        ->first();
        
            if ($newRankingDetails) {
                $newRanking = $newRankingDetails->id;

                // Only proceed with handling if the 'new' rank is different from the current rank
                if ($newRanking != $currentRanking) {
                    $newRankingDetails->image = $newRankingDetails->getFirstMediaUrl('ranking');

                    $currentRank = Ranking::where('id', $currentRanking)->first();

                    // Get the ranks that has been achieved through the increase of customer total spending from the current rank's min amount
                    $ranksAchieved = Ranking::select('id')
                                            ->with(['rankingRewards' => function ($query) {
                                                $query->where('status', 'Active')
                                                        ->select('id', 'ranking_id', 'reward_type', 'bonus_point');
                                            }])
                                            ->where([
                                                ['reward', 'active'],
                                                ['min_amount', '<=', $customer->total_spending],
                                                ['min_amount', '>', $currentRank->min_amount]
                                            ])
                                            ->orderBy('min_amount', 'asc')
                                            ->get();

                    foreach ($ranksAchieved as $rank) {
                        // Create customer rewardz
                        $rank->rankingRewards->each(function ($reward) use ($customer, $pointExpiredDate, $currentPoint){
                            CustomerReward::create([
                                'customer_id' => $customer->id,
                                'ranking_reward_id' => $reward->id,
                                'status' => $reward->reward_type === 'Bonus Point' ? 'Redeemed' : 'Active'
                            ]);

                            // Directly redeem the bonus point, creaie point history record and giving the point to the customer
                            if ($reward->reward_type === 'Bonus Point') {
                                $bonusPointReward = $reward->bonus_point;
                                
                                if ($bonusPointReward > 0) {                                    
                                    $afterReimburseBonusPoint = $currentPoint < 0 
                                            ? $currentPoint + $bonusPointReward 
                                            : $bonusPointReward;
                                            
                                    PointHistory::create([
                                        'product_id' => null,
                                        'payment_id' => null,
                                        'type' => 'Earned',
                                        'point_type' => 'Entry Reward',
                                        'qty' => 0,
                                        'amount' => $afterReimburseBonusPoint <= 0 ? 0 : $afterReimburseBonusPoint,
                                        'old_balance' => $currentPoint,
                                        'new_balance' => $currentPoint + $bonusPointReward,
                                        'expire_balance' => $afterReimburseBonusPoint <= 0 ? 0 : $afterReimburseBonusPoint,
                                        'expired_at' => $pointExpiredDate,
                                        'customer_id' => $customer->id,
                                        'handled_by' => 1,
                                        'redemption_date' => now()
                                    ]);

                                    $currentPoint += $bonusPointReward;
                                }
                            }
                        });
                    }

                    $customer->update([
                        'ranking' => $newRanking,
                        'point' => $currentPoint
                    ]);
                }
            } 
        }
    }
}
