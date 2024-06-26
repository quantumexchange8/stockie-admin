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
        'point',
        'category_id',
        'keep',
    ];
    
    // /**
    //  * Category Model
    //  * Get the category of the product.
    //  */
    // public function category(): BelongsTo
    // {
    //     return $this->belongsTo(Category::class, 'category_id');
    // }
    
    /**
     * ProductItem Model
     * Get the product items of the product.
     */
    public function productItems(): HasMany
    {
        return $this->hasMany(ProductItem::class, 'product_id');
    }
}
