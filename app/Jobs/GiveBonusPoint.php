<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\PointHistory;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class GiveBonusPoint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $paymentId;
    private int $totalPoints;
    private int $oldPoint;
    private int $customerId;
    private int $handledBy;
    /**
     * Create a new job instance.
     */
    public function __construct(int $paymentId, int $totalPoints, int $oldPoint, int $customerId, int $handledBy)
    {
        $this->paymentId = $paymentId;
        $this->totalPoints = $totalPoints;
        $this->oldPoint = $oldPoint;
        $this->customerId = $customerId;
        $this->handledBy = $handledBy;
        $this->queue = 'give-bonus-point';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $customer = Customer::find($this->customerId);
        if($customer){
            $pointExpirationDays = Setting::where([
                                                ['name', 'Point Expiration'],
                                                ['type', 'expiration']
                                            ])
                                            ->first(['id', 'value']);

            $pointExpiredDate = now()->addDays((int)$pointExpirationDays->value);
            
            $afterReimbursePoint = $this->oldPoint < 0 
                    ? $this->oldPoint + $this->totalPoints 
                    : $this->totalPoints;

            PointHistory::create([
                'product_id' => null,
                'payment_id' => $this->paymentId,
                'type' => 'Earned',
                'point_type' => 'Order',
                'qty' => 0,
                'amount' => $afterReimbursePoint <= 0 ? 0 : $afterReimbursePoint,
                'old_balance' => $this->oldPoint,
                'new_balance' => $customer->point,
                'expire_balance' => $afterReimbursePoint <= 0 ? 0 : $afterReimbursePoint,
                'expired_at' => $pointExpiredDate,
                'customer_id' => $this->customerId,
                'handled_by' => $this->handledBy,
                'redemption_date' => now()
            ]);

            $pointBonus = CustomerReward::where([
                                            ['customer_id', $this->customerId], 
                                            ['status', 'Active']
                                        ])
                                        ->with(['rankingReward' => fn($query) => $query->where('reward_type', 'Bonus Point')])
                                        ->get();

            $bonusPointsTotal = 0;

            foreach ($pointBonus as $point) {
                if ($point->rankingReward && $point->rankingReward->bonus_point) {
                    $bonusPointsTotal += $point->rankingReward->bonus_point;
                    $afterReimburseBonusPoint = $customer->point < 0 
                            ? $customer->point + $point->rankingReward->bonus_point 
                            : $point->rankingReward->bonus_point;

                    $point->update(['status' => 'Redeemed']);

                    PointHistory::create([
                        'product_id' => null,
                        'payment_id' => null,
                        'type' => 'Earned',
                        'point_type' => 'Entry Reward',
                        'qty' => 0,
                        'amount' => $afterReimburseBonusPoint <= 0 ? 0 : $afterReimburseBonusPoint,
                        'old_balance' => $customer->point,
                        'new_balance' => $customer->point + $point->rankingReward->bonus_point,
                        'expire_balance' => $afterReimburseBonusPoint <= 0 ? 0 : $afterReimburseBonusPoint,
                        'expired_at' => $pointExpiredDate,
                        'customer_id' => $this->customerId,
                        'handled_by' => $this->handledBy,
                        'redemption_date' => now()
                    ]);
                }
            }

            if ($bonusPointsTotal > 0) {
                $customer->increment('point', $bonusPointsTotal);
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
