<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Waiter extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    protected $fillable = [
        'name', 'phone', 'email', 'staffid', 'salary', 'stockie_email', 'stockie_password'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function keepItems () : HasMany
    {
        return $this->hasMany(KeepItem::class);
    }
    
    /**
     * Get the orders served by the waiter.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    // public function attendances () : HasMany
    // {
    //     return $this->hasMany(WaiterAttendance::class, 'user_id');
    // }

    public function configIncentEmployee () : HasMany
    {
        return $this->hasMany(ConfigIncentiveEmployee::class, 'user_id');
    }
}
