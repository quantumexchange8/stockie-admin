<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeIncentive extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "employee_incentives";

    protected $fillable = [
        'user_id',
        'incentive_id',
        'type',
        'rate',
        'amount', 
        'sales_target', 
        'recurring_on', 
        'effective_date', 
        'period_start', 
        'period_end', 
        'status', 
    ];

    /**
     * Get the waiter who earned the commission.
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the associated incentive.
     */
    public function incentive(): BelongsTo
    {
        return $this->belongsTo(ConfigIncentive::class, 'incentive_id');
    }
}
