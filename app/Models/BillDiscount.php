<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillDiscount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "bill_discounts";

    protected $fillable = [
        'name',
        'discount_type',
        'discount_rate',
        'discount_from',
        'discount_to',
        'available_on',
        'start_time',
        'end_time',
        'criteria',
        'requirement',
        'is_stackable',
        'conflict',
        'customer_usage',
        'customer_usage_renew',
        'total_usage',
        'total_usage_renew',
        'tier',
        'payment_method',
        'is_auto_applied',
        'status'
    ];
    
    protected $casts = [
        'tier' => 'array',
        'payment_method' => 'array',
        'is_stackable' => 'boolean',
        'is_auto_applied' => 'boolean',
        'discount_from' => 'datetime:Y-m-d H:i:s',
        'discount_to' => 'datetime:Y-m-d H:i:s',
        'customer_usage' => 'integer', 
        'total_usage' => 'integer',
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    public function billDiscountUsages(): HasMany
    {
        return $this->hasMany(BillDiscountUsage::class, 'bill_discount_id');
    }
}
