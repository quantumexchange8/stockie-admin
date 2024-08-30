<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "order_items";

    protected $fillable = [
        'order_id',
        'type',
        'item_id',
        'item_qty',
        'serve_qty',
        'amount',
        'point',
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
}
