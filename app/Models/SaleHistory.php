<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "sale_histories";

    protected $fillable = [
        'order_id',
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

    /**
     * Order Model
     * Get the order of the sale histories.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function scopeSoldToday($query)
    {
        return $query->join('orders', 'sale_histories.order_id', '=', 'orders.id')
                        ->whereDate('orders.created_at', Carbon::today())
                        ->sum('sale_histories.qty');
    }

    public function scopeSoldYesterday($query)
    {
        return $query->join('orders', 'sale_histories.order_id', '=', 'orders.id')
                        ->whereDate('orders.created_at', Carbon::yesterday())
                        ->sum('sale_histories.qty');
    }
}
