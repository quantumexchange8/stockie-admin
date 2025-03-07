<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    //

    protected $fillable = [

        'shift_name',
        'shift_code',
        'shift_start',
        'shift_end',
        'late',
        'color',
        'apply_days',
    ];
    
    protected $casts = [
        'apply_days' => 'array',
    ];

    public function shift_break(): BelongsTo
    {
        return $this->belongsTo(ShiftBreak::class, 'shift_id', 'id');
    }

    public function shift_breaks(): HasMany
    {
        return $this->hasMany(ShiftBreak::class, 'shift_id');
    }


}
