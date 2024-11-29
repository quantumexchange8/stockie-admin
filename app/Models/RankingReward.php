<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RankingReward extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "ranking_rewards";

    protected $fillable = [
        'ranking_id',
        'reward_type',
        'min_purchase',
        'discount',
        'min_purchase_amount',
        'valid_period_from',
        'valid_period_to',
        'free_item',
        'item_qty',
        'bonus_point'
    ];

    public function ranking(): BelongsTo
    {
        return $this->belongsTo(Ranking::class, 'ranking_id');
    }

    /**
     * IventoryItem Model
     * Get the inventory item of the ranking reward.
     */
    // public function inventoryItem(): BelongsTo
    // {
    //     return $this->belongsTo(IventoryItem::class, 'free_item');
    // }

    /**
     * Product Model
     * Get the product of the ranking reward.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'free_item');
    }

    /**
     * CustomerReward Model
     * Get rewards that belongs to customers.
     */
    public function customerReward(): HasMany
    {
        return $this->hasMany(CustomerReward::class, 'ranking_reward_id');
    }

    /**
     * Order Model
     * Get the orders that has this voucher(ranking reward) applied to it.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'voucher_id');
    }

    /**
     * Payment Model
     * Get the payments that has this voucher(ranking reward) applied to it.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'discount_id');
    }
}
