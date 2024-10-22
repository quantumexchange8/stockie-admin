<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigDiscountItem extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "config_discount_items";

    protected $fillable = [
        'discount_id',
        'product_id',
        'price_before',
        'price_after',
    ];
}
