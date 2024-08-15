<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;


class Table extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['type', 'table_no', 'seat', 'zone_id', 'status'];
    
    public function zones(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'id');
    }
}
