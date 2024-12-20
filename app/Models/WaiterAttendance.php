<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaiterAttendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "waiter_attendances";

    protected $fillable = [
        'user_id',
        'check_in',
        'check_out',  
        'status'
    ];

    public function waiters(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
