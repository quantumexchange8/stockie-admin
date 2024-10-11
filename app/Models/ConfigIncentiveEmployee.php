<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigIncentiveEmployee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'incentive_id',
        'user_id',
        'status'
    ];

    public function waiters() : HasMany
    {
        return $this->hasMany(User::class, 'id', 'user_id');
    }

    public function configIncentive() : BelongsTo
    {
        return $this->belongsTo(ConfigIncentive::class, 'incentive_id');
    }
}
