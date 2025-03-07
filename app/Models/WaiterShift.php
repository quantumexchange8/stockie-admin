<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaiterShift extends Model
{

    use SoftDeletes;

    //
    protected $fillable = [
        'waiter_id',
        'shift_id',
        'weeks',
        'days',
        'date',
        'week_range',
    ];

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id', 'id');
    }

    public function shifts(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }
    
}
