<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Reservation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $table = "reservations";

    protected $fillable = [
        'reservation_no',
        'customer_id',
        'name',
        'pax',
        'table_no',
        'phone',
        'cancel_type',
        'remark',
        'status',
        'reservation_date',
        'action_date',
        'order_id',
        'handled_by',
        'reserved_by'
    ];

    protected $casts = ['table_no' => 'json'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    /**
     * Get the user who handled this reservation. (ie: checked in customer, etc)
     */
    public function reservedFor(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the user who handled this reservation. (ie: checked in customer, etc)
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Get the user who reserved this reservation.
     */
    public function reservedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    /**
     * Get the order opened for this reservation.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
