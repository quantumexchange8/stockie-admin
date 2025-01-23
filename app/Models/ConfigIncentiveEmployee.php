<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConfigIncentiveEmployee extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'incentive_id',
        'user_id',
        'status'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function waiters() : HasMany
    {
        return $this->hasMany(User::class, 'id', 'user_id');
    }

    public function configIncentive() : BelongsTo
    {
        return $this->belongsTo(ConfigIncentive::class, 'incentive_id');
    }
}
