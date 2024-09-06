<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeepItem extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "keep_items";

    protected $fillable = [
        'customer_id',
        'order_item_id',
        'qty',
        'cm',
        'remark',
        'waiter_id',
        'status',
        'expired_from',
        'expired_to',
    ];

    /**
     * KeepHistory Model
     * Get the keep histories of the keep.
     */
    public function keepHistories(): HasMany
    {
        return $this->hasMany(KeepHistory::class, 'keep_item_id');
    }

    /**
     * OrderItem Model
     * Get the order item of the keep item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
