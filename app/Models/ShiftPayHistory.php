<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ShiftPayHistory extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = "shift_pay_histories";

    protected $fillable = [
        'shift_transaction_id',
        'user_id',
        'type',
        'amount',
        'reason'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    /**
     * Get the shift transaction.
     */
    public function shiftTransaction(): BelongsTo
    {
        return $this->belongsTo(ShiftTransaction::class, 'shift_transaction_id');
    }

    /**
     * Get the employee that made the pay transaction.
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
