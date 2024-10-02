<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeepItem extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "keep_items";

    protected $fillable = [
        'customer_id',
        'order_item_subitem_id',
        'qty',
        'cm',
        'remark',
        'waiter_id',
        'status',
        'expired_from',
        'expired_to',
    ];
    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function waiters(): BelongsTo
    {
        return $this->belongsTo(Waiter::class,'waiter_id');
    }
    
     /**
     * OrderItemSubitem Model
     * Get the order item subitem that is being kept.
     */
    public function orderItemSubitem(): BelongsTo
    {
        return $this->belongsTo(OrderItemSubitem::class,'order_item_subitem_id');
    }

    public function keepHistories(): HasMany
    {
        return $this->hasMany(KeepHistory::class,'keep_item_id');
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