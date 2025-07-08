<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConfigPrinter extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = "config_printers";

    protected $fillable = [
        'name',
        'ip_address',
        'port_number',
        'kick_cash_drawer',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
