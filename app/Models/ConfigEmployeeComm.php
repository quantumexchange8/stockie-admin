<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigEmployeeComm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'comm_type',
        'rate'
    ];

    public function configCommItems(): HasMany
    {
        return $this->hasMany(ConfigEmployeeCommItem::class, 'comm_id');
    }
}
