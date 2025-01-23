<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KeepItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $table = "keep_items";

    protected $fillable = [
        'customer_id',
        'order_item_subitem_id',
        'qty',
        'cm',
        'remark',
        'user_id',
        'status',
        'expired_from',
        'expired_to',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }
    
     /**
     * OrderItemSubitem Model
     * Get the order item subitem that is being kept.
     */
    public function orderItemSubitem(): BelongsTo
    {
        return $this->belongsTo(OrderItemSubitem::class,'order_item_subitem_id');
    }

    /**
     * KeepHistory Model
     * Get all the histories of the keep item.
     */
    public function keepHistories(): HasMany
    {
        return $this->hasMany(KeepHistory::class,'keep_item_id');
    }

    /**
     * KeepHistory Model
     * Get the latest history of the keep item.
     */
    public function latestKeepHistory()
    {
        return $this->hasOne(KeepHistory::class, 'keep_item_id')->latest()->limit(1);
    }

    /**
     * KeepHistory Model
     * Get the oldest history of the keep item.
     */
    public function oldestKeepHistory()
    {
        return $this->hasOne(KeepHistory::class, 'keep_item_id')->oldest()->limit(1);
    }

    /**
    * OrderItem Model
    * Get the keep item that has been added to an order.
    */
   public function orderItem(): HasOne
   {
       return $this->hasOne(OrderItem::class,'keep_item_id');
   }

}