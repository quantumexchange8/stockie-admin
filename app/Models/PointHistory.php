<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PointHistory extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, LogsActivity;

    protected $table = "point_histories";

    protected $fillable = [
        'product_id',
        'payment_id',
        'type',
        'point_type',
        'qty',
        'amount',
        'old_balance',
        'new_balance',
        'remark',
        'customer_id',
        'handled_by',
        'redemption_date'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
    
    /**
     * Product Model
     * Get the redeemable item of the point history record.
     */
    public function redeemableItem(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    /**
     * Payment Model
     * Get the order payment of which the point history is recorded.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
    
    /**
     * Customer Model
     * Get the customer that earned or spent the point.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    
    /**
     * User Model
     * Get the user that redeemed the item.
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
