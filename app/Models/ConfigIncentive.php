<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigIncentive extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'rate',
        'effective_date',
        'recurring_on',
        'monthly_sale',
    ];

    public function incentiveEmployees() : HasMany
    {
        return $this->hasMany(ConfigIncentiveEmployee::class, 'incentive_id');
    }

    
}
