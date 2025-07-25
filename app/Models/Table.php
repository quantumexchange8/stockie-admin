<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Table extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    protected $fillable = [
        'type', 
        'table_no', 
        'seat', 
        'zone_id', 
        'status', 
        'order_id',
        'state',
        'is_locked',
        'locked_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
    
    public function zones(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'id');
    }
    
    /**
     * OrderTable Model
     * Get the order tables of the table.
     */
    public function orderTables(): HasMany
    {
        return $this->hasMany(OrderTable::class, 'table_id');
    }

    /**
     * Order Model
     * Get the order of the table.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
