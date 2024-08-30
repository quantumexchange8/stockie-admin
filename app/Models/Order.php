<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "orders";

    protected $fillable = [
        'order_no',
        'pax',
        'waiter_id',
        'customer_id',
        'total_amount',
        'voucher',
        'status',
    ];

    
    /**
     * Get the order table of the order.
     */
    public function orderTable(): HasOne
    {
        return $this->hasOne(OrderTable::class, 'order_id');
    }

    /**
     * OrderItem Model
     * Get the items of the order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
