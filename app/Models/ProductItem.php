<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = "product_items";

    protected $fillable = [
        'product_id',
        'inventory_item_id',
        'qty',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
    
    /**
     * Product Model
     * Get the product of the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    /**
     * InventoryItem Model
     * Get the inventory item of the product item.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(IventoryItem::class, 'inventory_item_id');
    }

    /**
     * OrderItemSubitem Model
     * Get the ordered sub items (product item).
     */
    public function orderSubitems(): HasMany
    {
        return $this->hasMany(OrderItemSubitem::class, 'product_item_id');
    }
}
