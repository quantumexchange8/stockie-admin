<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'user_id',
        'customer_id',
        'amount',
        'voucher_id',
        'total_amount',
        'discount_amount',
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
     * Get the waiter of the order.
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * OrderItem Model
     * Get the items of the order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    
    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class, 'redeem_by', 'customer_id');
    }

    /**
     * Get the customer of the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the reservation made.
     */
    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class, 'order_id');
    }
}
