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
        'status'
    ];
    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function waiters(): BelongsTo
    {
        return $this->belongsTo(Waiter::class,'waiter_id');
    }
    
    public function orderItemSubitem(): BelongsTo
    {
        return $this->belongsTo(OrderItemSubitem::class,'order_item_subitem_id');
    }

    public function keepHistories(): HasMany
    {
        return $this->hasMany(KeepHistory::class,'keep_item_id');
    }

}