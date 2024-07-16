<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeepHistory extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "keep_histories";

    protected $fillable = [
        'item',
        'qty',
        'cm',
        'keep_date',
        'expired_from',
        'expired_to',
        'status',
    ];
}
