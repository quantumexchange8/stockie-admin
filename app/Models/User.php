<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
/**
 * @method HasMany attendances()
 */

class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, SoftDeletes, InteractsWithMedia, LogsActivity, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'worker_email', 
        'phone', 
        'password',  
        'position',
        'position_id', 
        'role_id', 
        'passcode', 
        'passcode_status', 
        'profile_photo',
        'employment_type',
        'salary', 
        'status', 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //only the `created` event will get logged automatically
    protected static $recordEvents = ['created'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    /**
     * PointHistory Model
     * Get the histories of the point(redeemable item) redeemed by the user.
     */
    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class, 'handled_by');
    }

    /**
     * OrderItem Model
     * Get the order items ordered by the user.
     */
    public function orderedItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'user_id');
    }

    public function keepItems(): HasMany
    {
        return $this->hasMany(KeepItem::class);
    }

    public function keepHistories(): HasMany
    {
        return $this->hasMany(KeepHistory::class);
    }
    
    /**
     * Get the orders served by the waiter.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function latestAttendance(): HasOne
    {
        return $this->hasOne(WaiterAttendance::class, 'user_id')
                    ->where(function ($query) {
                        $query->where('status', 'Checked in')
                            ->orWhere('status', 'Checked out');
                    })
                    ->latest();
    }

    public function latestBreak(): HasOne
    {
        return $this->hasOne(WaiterAttendance::class, 'user_id')
                    ->where(function ($query) {
                        $query->where('status', 'Break start')
                            ->orWhere('status', 'Break end');
                    })
                    ->latest();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(WaiterAttendance::class, 'user_id');
    }

    public function configIncentEmployee(): HasMany
    {
        return $this->hasMany(ConfigIncentiveEmployee::class, 'user_id');
    }

    /**
     * OrderTable Model
     * Get the order tables checked in by the user.
     */
    public function tablesCheckedIn(): HasMany
    {
        return $this->hasMany(OrderTable::class, 'user_id');
    }

    /**
     * Payment Model
     * Get the order payment receipts.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'handled_by', 'id');
    }

    /**
     * EmployeeCommission Model
     * Get the commissions earned by the employee.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(EmployeeCommission::class, 'user_id');
    }

    /**
     * OrderItem Model
     * Get the order items that are served of which it's order is completed and payment has been made.
     */
    public function sales()
    {
        return OrderItem::itemSales()->where('order_items.user_id', $this->id);                        
    }

    /**
     * EmployeeIncentive Model
     * Get the incentives achieved by the employee.
     */
    public function incentives(): HasMany
    {
        return $this->hasMany(EmployeeIncentive::class, 'user_id');
    }

     /**
     * Get the shifts assigned to the user.
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(WaiterShift::class, 'waiter_id');
    }

     /**
     * Get the shift transaction opened by the user.
     */
    public function openedShiftTransactions(): HasMany
    {
        return $this->hasMany(ShiftTransaction::class, 'opened_by');
    }

     /**
     * Get the shift pay in and pay out histories of the transaction closed by the user.
     */
    public function shiftPayHistories(): HasMany
    {
        return $this->hasMany(ShiftPayHistory::class, 'user_id');
    }

     /**
     * Get the shift transaction closed by the user.
     */
    public function closedShiftTransactions(): HasMany
    {
        return $this->hasMany(ShiftTransaction::class, 'closed_by');
    }
    
    /**
     * WaiterPosition Model
     * Get the position for the waiter users.
     */
    public function waiterPosition(): BelongsTo
    {
        return $this->belongsTo(WaiterPosition::class, 'position_id');
    }


    // /**
    //  * Reservation Model
    //  * Get the reservations handled by the user.
    //  */
    // public function reservationsHandled(): HasMany
    // {
    //     return $this->hasMany(Reservation::class, 'handled_by', 'user_id');
    // }

    // /**
    //  * Reservation Model
    //  * Get the reservations made by the user.
    //  */
    // public function reservationsMade(): HasMany
    // {
    //     return $this->hasMany(Reservation::class, 'reserved_by', 'user_id');
    // }
}
