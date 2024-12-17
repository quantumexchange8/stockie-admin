<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\Ranking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class GiveEntryReward implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $oldRanking;
    private int $customerId;
    private float $oldSpending;

    /**
     * Create a new job instance.
     */
    public function __construct(int $oldRanking, int $customerId, float $oldSpending)
    {
        $this->oldRanking = $oldRanking;
        $this->customerId = $customerId;
        $this->oldSpending = $oldSpending;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // $bonusPointRewards = [];
        $customer = Customer::find($this->customerId);
        if($customer) {
            if ($customer->ranking != $this->oldRanking) {
                $ranks = Ranking::select('id')
                                ->with(['rankingRewards' => function ($query) {
                                    $query->where('status', 'Active')
                                            ->select('id', 'ranking_id', 'reward_type', 'bonus_point');
                                }])
                                ->where([
                                    ['reward', 'active'],
                                    ['min_amount', '<=', $customer->total_spending],
                                    ['min_amount', '>', $this->oldSpending]
                                ])
                                ->orderBy('min_amount', 'asc')
                                ->get();

                foreach ($ranks as $rank) {
                    $rank->rankingRewards->each(function ($reward) use ($customer){
                        CustomerReward::create([
                            'customer_id' => $customer->id,
                            'ranking_reward_id' => $reward->id,
                            'status' => 'Active'
                        ]);
                    });
                }
            }
        }
    }

    public function middleware()
    {
        return [new WithoutOverlapping($this->customerId)];
        
    }

    /**
     * Determine number of times the job may be attempted.
     */
    public function tries(): int
    {
        return 5;
    }
}
