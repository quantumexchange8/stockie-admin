<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConfigIncentive extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'type',
        'rate',
        'effective_date',
        'recurring_on',
        'monthly_sale',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function incentiveEmployees() : HasMany
    {
        return $this->hasMany(ConfigIncentiveEmployee::class, 'incentive_id');
    }

    /**
     * Get all the earned incentives.
     */
    public function earnedIncentives() : HasMany
    {
        return $this->hasMany(EmployeeIncentive::class, 'incentive_id');
    }
}
