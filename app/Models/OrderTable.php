<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrderTable extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $table = "order_tables";

    protected $fillable = [
        'table_id',
        'reservation',
        'pax',
        'user_id',
        'status',
        'reservation_date',
        'order_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    /**
     * Order Model
     * Get the order of the order table.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Table Model
     * Get the table of the order table.
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    /**
     * User Model
     * Get the user that checked the customer in.
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
