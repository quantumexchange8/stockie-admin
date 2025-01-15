<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MSICCodes extends Model
{
    use HasFactory;

    protected $table = "m_s_i_ccodes";

    protected $fillable = [
        'Code',
        'Description',
        'MSIC Category Reference'
    ];
}
