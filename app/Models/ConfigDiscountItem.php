<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConfigDiscountItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $table = "config_discount_items";

    protected $fillable = [
        'discount_id',
        'product_id',
        'price_before',
        'price_after',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(ConfigDiscount::class, 'discount_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    /**
     * OrderItem Model
     * Get the order items that has this discount applied.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'discount_id');
    }
}
