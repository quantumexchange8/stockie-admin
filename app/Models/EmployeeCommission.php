<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeCommission extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "employee_commissions";

    protected $fillable = [
        'user_id',
        'type',
        'rate',
        'order_item_id', 
        'comm_item_id', 
        'amount', 
    ];

    /**
     * Get the waiter who earned the commission.
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * ConfigEmployeeCommItem Model
     * Get the config commission item  of the respective commission.
     */
    public function commItem(): BelongsTo
    {
        return $this->belongsTo(ConfigEmployeeCommItem::class, 'comm_item_id', 'id');
    }
    
    /**
     * OrderItem Model
     * Get the order item to the respective commission.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }
}
