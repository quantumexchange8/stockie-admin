<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class WaiterPosition extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = "waiter_positions";

    protected $fillable = ['name'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    /**
     * User Model
     * Get the users that has waiter position.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'position_id');
    }
}
