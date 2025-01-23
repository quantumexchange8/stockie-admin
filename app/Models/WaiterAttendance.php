<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WaiterAttendance extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = "waiter_attendances";

    protected $fillable = [
        'user_id',
        'check_in',
        'check_out',  
        'status'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function waiters(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
