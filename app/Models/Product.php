<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "products";

    protected $fillable = [
        'product_name',
        'price',
        'bucket',
        'point',
        'category_id',
        'keep',
    ];
    
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
}
