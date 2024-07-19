<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockHistory extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "stock_histories";

    protected $fillable = [
        'inventory_id',
        'inventory_item',
        'old_stock',
        'in',
        'out',
        'current_stock',
        'date',
    ];
    
    /**
     * Iventory Model
     * Get the inventory of the stock history.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Iventory::class);
    }
}
