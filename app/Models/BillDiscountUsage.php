<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillDiscountUsage extends Model
{
    use HasFactory;

    protected $table = "bill_discount_usages";

    protected $fillable = [
        'bill_discount_id',
        'customer_id',
        'customer_usage',
        'total_usage',
    ];

    /*
        Bill Discount model.
        Get the Bill Discount that has this record.
    */
    public function billDiscount(): BelongsTo
    {
        return $this->belongsTo(BillDiscount::class, 'bill_discount_id');
    }

    /*
        Customer model.
        Get the customer whose usage is being tracked by this record.
    */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
