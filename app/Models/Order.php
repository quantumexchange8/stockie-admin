<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Order extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
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

    //only the `created` event will get logged automatically
    // protected static $recordEvents = ['created'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    /**
     * Get the order table of the order.
     */
    public function orderTable(): HasMany
    {
        return $this->hasMany(OrderTable::class, 'order_id');
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

    
    // public function pointHistories(): HasMany
    // {
    //     return $this->hasMany(PointHistory::class, 'handled_by', 'customer_id');
    // }

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

    /**
     * Get the payment receipt of the order.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'order_id');
    }

    /**
     * RankingReward Model
     * Get the associated voucher(ranking reward).
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(RankingReward::class,'voucher_id');
    }
}
