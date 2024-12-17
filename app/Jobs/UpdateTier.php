<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Ranking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class UpdateTier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $customerId;
    private int $points;
    private float $spending;

    /**
     * Create a new job instance.
     */
    public function __construct(int $customerId, int $points, float $spending)
    {
        $this->customerId = $customerId;
        $this->points = $points;
        $this->spending = $spending;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $newRankingDetails = Ranking::where('min_amount', '<=', $this->spending)
                                    ->select('id', 'name')
                                    ->orderBy('min_amount', 'desc')
                                    ->first();
    
        if ($newRankingDetails) {
            $newRankingDetails->image = $newRankingDetails->getFirstMediaUrl('ranking');
            $newRanking = $newRankingDetails->id;
    
            $customer = Customer::find($this->customerId);
            if ($customer) {
                $customer->update([
                    'point' => $this->points,
                    'total_spending' => $this->spending,
                    'ranking' => $newRanking
                ]);
                $customer->refresh();
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
