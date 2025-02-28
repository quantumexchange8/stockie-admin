<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    //

    protected $fillable = [

        'shift_name',
        'shift_code',
        'time_start',
        'time_end',
        'late',
        'color',
        'days',
    ];
    
}
