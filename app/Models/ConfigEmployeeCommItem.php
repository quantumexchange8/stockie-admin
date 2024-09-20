<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigEmployeeCommItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'comm_id',
        'item'
    ];

    public function configComms() : BelongsTo
    {
        return $this->belongsTo(ConfigEmployeeComm::class, 'comm_id');
    }

    public function productItems() : HasMany
    {
        return $this->hasMany(ProductItem::class, 'id', 'item');
    }
}
