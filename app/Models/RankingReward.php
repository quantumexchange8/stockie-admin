<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RankingReward extends Model
{
    use HasFactory;
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
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(IventoryItem::class, 'free_item');
    }
}
