<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftBreak extends Model
{
    //

    protected $fillable = [
        'shift_id',
        'break_value',
        'break_time',
    ];
}
