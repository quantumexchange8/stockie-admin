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
        'type',
        'amount',
        'reason',
        'user_id',
        'shift_transaction_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
