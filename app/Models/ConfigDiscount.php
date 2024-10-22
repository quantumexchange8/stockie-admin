<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigDiscount extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "config_discounts";

    protected $fillable = [
        'name',
        'type',
        'rate',
        'discount_from',
        'discount_to',
    ];
}
