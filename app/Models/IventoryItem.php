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
        'low_stock_qty',
        'keep',
        'status',
    ];
    
    /**
     * Iventory Model
     * Get the inventory of the inventory item.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Iventory::class, 'inventory_id');
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
    // public function rankingRewards(): HasMany
    // {
    //     return $this->hasMany(RankingReward::class, 'free_item');
    // }
    
    /**
     * PointItem Model
     * Get the point items of the inventory item.
     */
    // public function pointItems(): HasMany
    // {
    //     return $this->hasMany(PointItem::class, 'inventory_item_id');
    // }


    // Register the model event
    protected static function booted()
    {
        static::updated(function ($inventoryItem) {
            // Only trigger the event if stock_qty has changed
            if ($inventoryItem->isDirty('stock_qty')) {
                // When stock quantity is updated, update the status of related products
                $inventoryItem->updateProductStatus();
            }
        });
    }

    // Define the method to update the product status
    public function updateProductStatus()
    {
        // Get all product items related to this inventory item
        $productItems = $this->productItems()->with('product')->get();

        foreach ($productItems as $productItem) {
            // Update the status of the related product based on its product items
            $productItem->product->updateStatus();
        }
    }
}
