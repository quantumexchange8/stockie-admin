<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $table = "payments";

    protected $fillable = [
        'transaction_id',
        'order_id',
        'table_id',
        'receipt_no',
        'receipt_start_date',
        'receipt_end_date',
        'total_amount',
        'rounding',
        'sst_amount',
        'service_tax_amount',
        'discount_id',
        'discount_amount',
        'bill_discounts',
        'bill_discount_total',
        'grand_total',
        'amount_paid',
        'change',
        'point_earned',
        'pax',
        'status',
        'invoice_status',
        'customer_id',
        'handled_by',
        'consolidated_parent_id',
        'submitted_uuid',
        'submission_date',
    ];

    protected $casts = [
        'table_id' => 'json',
        'bill_discounts' => 'json'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    /**
     * Get the order of the payment.
     */
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

    /**
     * Get the user that handled the payment.
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Get the point history created upon order payment.
     */
    public function pointHistory(): HasOne
    {
        return $this->hasOne(PointHistory::class, 'payment_id');
    }

    /**
     * RankingReward Model
     * Get the associated voucher(ranking reward).
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(RankingReward::class,'discount_id');
    }

    /**
     * ShiftTransaction Model
     * Get the shift transaction of this order.
     */
    public function shiftTransaction(): BelongsTo
    {
        return $this->belongsTo(ShiftTransaction::class,'transaction_id');
    }
}
