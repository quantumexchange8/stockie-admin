<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Iventory extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "iventories";

    protected $fillable = [
        'name',
        'category_id',
        'image',
    ];
    
    /**
     * IventoryItem Model
     * Get the inventory items of the inventory.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(IventoryItem::class, 'inventory_id', 'id');
    }
    
    /**
     * Category Model
     * Get the category of the inventory.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * StockHistory Model
     * Get the stock histories of the inventory..
     */
    public function stockHistories(): HasMany
    {
        return $this->hasMany(StockHistory::class, 'inventory_id', 'id');
    }
}
