<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "item_categories";

    protected $fillable = [
        'type',
        'name',
        'low_stock_qty',
    ];

    /**
     * IventoryItem Model
     * Get the inventory items that has this item category.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(IventoryItem::class, 'item_cat_id');
    }
}
