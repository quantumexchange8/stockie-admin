<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ShiftTransaction extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = "shift_transactions";

    protected $fillable = [
        'opened_by',
        'closed_by',
        'shift_no',
        'starting_cash',
        'paid_in',
        'paid_out',
        'cash_refund',
        'expected_cash',
        'cash_sales',
        'card_sales',
        'ewallet_sales',
        'gross_sales',
        'sst_amount',
        'service_tax_amount',
        'total_refund',
        'total_void',
        'total_discount',
        'net_sales',
        'closing_cash',
        'difference',
        'shift_opened',
        'shift_closed',
        'status'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    /**
     * Get the currently opened shift.
     */
    public function openedShift()
    {
        return $this->hasOne(ShiftTransaction::class)->where('status', 'opened')->latest()->limit(1);
    }
    
    /**
     * Check if there is an opened shift.
     */
    public static function hasOpenedShift()
    {
        return self::where('status', 'opened')->exists();
    }

    /**
     * Get the employee that opened the shift.
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * Get the employee that closed the shift.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Get the shift transaction closed by the user.
     */
    public function shiftPayHistories(): HasMany
    {
        return $this->hasMany(ShiftPayHistory::class, 'shift_transaction_id');
    }

    /**
     * Get the payments made under this shift transaction.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'transaction_id');
    }
}
