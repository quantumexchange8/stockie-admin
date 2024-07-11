<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;
    public function ranking(): BelongsTo
    {
        return $this->belongsTo(Ranking::class,'ranking');
    }
}
