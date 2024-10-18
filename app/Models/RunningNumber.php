<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RunningNumber extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "running_numbers";

    protected $fillable = [
        'type',
        'prefix',
        'digits',
        'last_number',
    ];
}
