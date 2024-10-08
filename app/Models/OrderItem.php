<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "order_items";

    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'keep_item_id',
        'product_id',
        'item_qty',
        'amount',
        'point_earned',
        'point_redeemed',
        'status',
    ];

    /**
     * Order Model
     * Get the order of the item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * KeepHistory Model
     * Get the keep history of the order item.
     */
    public function keepHistory(): HasOne
    {
        return $this->hasOne(KeepHistory::class, 'order_item_id');
    }
    
    /**
     * Product Model
     * Get the product of the order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    /**
     * OrderItemSubitem Model
     * Get the sub items of the order item.
     */
    public function subItems(): HasMany
    {
        return $this->hasMany(OrderItemSubitem::class, 'order_item_id');
    }
    
    /**
     * User Model
     * Get the user who ordered the item.
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function point(): BelongsTo
    {
        return $this->belongsTo(Point::class, 'product_id');
    }

    /**
     * KeepItem Model
     * Get the keep item of the order item.
     */
    public function keepItem(): BelongsTo
    {
        return $this->belongsTo(KeepItem::class, 'keep_item_id');
    }
}
