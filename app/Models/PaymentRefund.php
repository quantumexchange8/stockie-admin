<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentRefund extends Model
{
    protected $fillable = [
        'payment_id',
        'customer_id',
        'refund_no',
        'subtotal_refund_amount',
        'refund_sst',
        'refund_service_tax',
        'refund_rounding',
        'total_refund_amount',
        'refund_method',
        'others_remark',
        'refund_remark',
        'status',
        'invoice_status',
        'consolidated_parent_id',
        'submitted_uuid',
        'uuid',
        'submission_date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the customer that made the payment.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function refund_details(): HasMany
    {
        return $this->hasMany(RefundDetail::class, 'payment_refund_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
