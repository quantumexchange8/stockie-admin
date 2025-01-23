<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KeepHistory extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $table = "keep_histories";

    protected $fillable = [
        'keep_item_id',
        'order_item_id',
        'qty',
        'cm',
        'keep_date',
        'remark',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    /**
     * KeepItem Model
     * Get the keep item of the keep history.
     */
    public function keepItem(): BelongsTo
    {
        return $this->belongsTo(KeepItem::class, 'keep_item_id');
    }

    /**
     * OrderItem Model
     * Get the order item of the keep history.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
