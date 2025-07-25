<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConfigEmployeeComm extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = "config_employee_comms";

    protected $fillable = [
        'comm_type',
        'rate',
        'status'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function configCommItems(): HasMany
    {
        return $this->hasMany(ConfigEmployeeCommItem::class, 'comm_id');
    }
}
