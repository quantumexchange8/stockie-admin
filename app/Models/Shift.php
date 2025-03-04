<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

}
