<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsolidatedInvoice extends Model
{
    //
    protected $fillable = [
        'c_invoice_no',
        'c_datetime',
        'docs_type',
        'c_period_start',
        'c_period_end',
        'cancel_expired_at',
        'submitted_uuid',
        'uuid',
        'remark',
        'c_amount',
        'c_total_amount',
        'status',
        'invoice_status',
        'consolidated_parent_id',
        'submitted_uuid',
        'uuid',
        'submission_date',
    ];

    public function invoice_child(): HasMany
    {
        return $this->hasMany(Payment::class, 'consolidated_parent_id', 'id');
    }

    public function invoice_no(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'c_invoice_no', 'receipt_no');
    }
}
