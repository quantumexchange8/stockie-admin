<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, LogsActivity;

    protected $table = "products";

    protected $fillable = [
        'product_name',
        'price',
        'bucket',
        'point',
        'category_id',
        'discount_id',
        // 'keep',
        'is_redeemable',
        'status',
        'availability',
        'image',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
    
    // /**
    //  * Category Model
    //  * Get the category of the product.
    //  */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    /**
     * ProductItem Model
     * Get the product items of the product.
     */
    public function productItems(): HasMany
    {
        return $this->hasMany(ProductItem::class, 'product_id');
    }
    
    /**
     * SaleHistory Model
     * Get the sale histories of the product.
     */
    public function saleHistories(): HasMany
    {
        return $this->hasMany(SaleHistory::class, 'product_id');
    }
    
    /**
     * OrderItem Model
     * Get the order items of the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function discountItems(): HasMany
    {
        return $this->hasMany(ConfigDiscountItem::class, 'product_id');
    }
    
    /**
     * ConfigDiscountItem Model
     * Get a filtered discount summary of the product.
     */
    public function discountSummary($discount_id = null)
    {
        return $discount_id 
            ? $this->discountItems->select('id', 'discount_id', 'product_id', 'price_before', 'price_after')
                                    ->where('discount_id', $discount_id) 
            : null;
    }

    /**
     * PointHistory Model
     * Get the order items of the product.
     */
    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class, 'product_id');
    }
    
    /**
     * RankingReward Model
     * Get the ranking rewards of the product.
     */
    public function rankingRewards(): HasMany
    {
        return $this->hasMany(RankingReward::class, 'free_item');
    }
    
    /**
     * ConfigDiscount Model
     * Get the current discount of the product.
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(ConfigDiscount::class, 'discount_id');
    }
    
    /**
     * ConfigEmployeeCommItem Model
     * Get the associated employee commission type's item.
     */
    public function commItem(): HasOne
    {
        return $this->hasOne(ConfigEmployeeCommItem::class, 'item');
    }

    // Register the model event
    protected static function booted()
    {
        static::updated(function ($product) {
            // Check if the status is dirty before calling updateStatus
            if ($product->isDirty('status')) {
                return;
            }
            // Update the product status
            $product->updateStatus();
        });
    }

    // Define the method to update the product status
    public function updateStatus()
    {
        $stockStatuses = [];
        $productItem = [];

        // Eager load the product items and their inventory item for performance
        $productItems = $this->productItems()->with(['inventoryItem.itemCategory'])->get();

        foreach ($productItems  as $item) {
            // Check the inventory stock level for each product item
            $inventoryItem = $item->inventoryItem;
            // $threshold = $inventoryItem->itemCategory->low_stock_qty;
            $requiredQuantity = (int) $item->qty;
            $availableStock = (int) $inventoryItem->stock_qty;

            // Determine the stock status based on the available stock and required quantity
            $stockStatuses[] = match (true) {
                $availableStock == 0 || $availableStock < $requiredQuantity => 'Out of stock',
                $availableStock <= $inventoryItem->low_stock_qty => 'Low in stock',
                default => 'In stock',
            };

            array_push($productItem, $item);
        }

        // Determine the overall product status
        $newStatus = match (true) {
            in_array('Out of stock', $stockStatuses) => 'Out of stock',
            in_array('Low in stock', $stockStatuses) => 'Low in stock',
            default => 'In stock',
        };

        // Save only if the status has changed
        if ($this->status !== $newStatus) {
            $this->status = $newStatus;
            $this->saveQuietly(); // Prevent triggering the updated event again
        }

    }
}
