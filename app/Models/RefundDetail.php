<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefundDetail extends Model
{
    protected $fillable = [
        'payment_refund_id',
        'order_item_id',
        'product_id',
        'refund_qty',
        'refund_amount',
    ];

    public function FilterOrderItems(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id')->where('type', 'Normal')->where('status', 'Served');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
