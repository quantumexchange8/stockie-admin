<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IventoryItem extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "iventory_items";

    protected $fillable = [
        'inventory_id',
        'item_name',
        'item_code',
        'item_cat_id',
        'stock_qty',
        'status',
    ];
    
    /**
     * Iventory Model
     * Get the inventory of the inventory item.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Iventory::class);
    }
    
    /**
     * ItemCategory Model
     * Get the item category of the inventory item.
     */
    public function itemCategory(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_cat_id');
    }
}
