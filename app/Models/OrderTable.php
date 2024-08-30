<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderTable extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "order_tables";

    protected $fillable = [
        'table_id',
        'reservation',
        'pax',
        'waiter_id',
        'status',
        'reservation_date',
        'order_id',
    ];

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

}
