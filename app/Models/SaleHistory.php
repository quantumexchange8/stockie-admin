<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "sale_histories";

    protected $fillable = [
        'product_id',
        'total_price',
        'qty',
    ];

    /**
     * Product Model
     * Get the product of the sale histories.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
