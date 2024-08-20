<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    
    /**
     * ProductItem Model
     * Get the product items of the inventory item.
     */
    public function productItems(): HasMany
    {
        return $this->hasMany(ProductItem::class, 'inventory_item_id');
    }
    
    /**
     * RankingReward Model
     * Get the ranking rewards of the inventory item.
     */
    public function rankingRewards(): HasMany
    {
        return $this->hasMany(RankingReward::class, 'free_item');
    }
    
    /**
     * PointItem Model
     * Get the point items of the inventory item.
     */
    public function pointItems(): HasMany
    {
        return $this->hasMany(PointItem::class, 'inventory_item_id');
    }
}
