<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerReward extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "customer_rewards";

    protected $fillable = [
        'customer_id',
        'ranking_reward_id',
        'status',
    ];

    /**
     * Customer Model
     * Get the customer that has this reward.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class,'customer_id');
    }

    /**
     * RankingReward Model
     * Get the associated ranking reward.
     */
    public function rankingReward(): BelongsTo
    {
        return $this->belongsTo(RankingReward::class,'ranking_reward_id');
    }
}
