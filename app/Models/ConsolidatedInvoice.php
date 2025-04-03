<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    ];

    public function invoice_child(): HasMany
    {
        return $this->hasMany(Payment::class, 'consolidated_parent_id', 'id');
    }
}
