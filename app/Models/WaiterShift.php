<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaiterShift extends Model
{
    //
    protected $fillable = [
        'waiter_id',
        'shift_id',
        'weeks',
        'days',
        'date',
    ];

    
}
