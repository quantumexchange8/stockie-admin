<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItemSubitem extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "order_item_subitems";

    protected $fillable = [
        'order_item_id',
        'product_item_id',
        'item_qty',
        'serve_qty',
    ];

    /**
     * OrderItem Model
     * Get the order item of the sub item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    /**
     * ProductItem Model
     * Get the product item of the sub item.
     */
    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class, 'product_item_id');
    }

    /**
     * KeepItem Model
     * Get the keep items of the order item subitem.
     */
    public function keepItems(): HasMany
    {
        return $this->hasMany(KeepItem::class, 'order_item_subitem_id');
    }
}
