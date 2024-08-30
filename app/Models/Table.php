<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Table extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'type', 
        'table_no', 
        'seat', 
        'zone_id', 
        'status', 
        'order_id'
    ];
    
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
}
