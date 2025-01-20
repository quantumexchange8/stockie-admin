<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'role_id', 
        'passcode', 
        'passcode_status', 
        'profile_photo',
        'salary', 
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
    
    /**
     * Get the orders served by the waiter.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function currentAttendance(): ?WaiterAttendance
    {
        return $this->hasOne(WaiterAttendance::class, 'user_id')
                    ->where('status', 'Checked in')
                    ->latest('check_in')
                    ->first();
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
     * Payment Model
     * Get the order payment receipts.
     */
    public function itemSales(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'user_id')
                    ->whereHas('order', function ($query) {
                        $query->where('status', 'Order Completed')
                                ->whereHas('payment', fn ($subQuery) => $subQuery->where('status', 'Successful'));
                    })
                    ->where([
                        ['type', 'Normal'],
                        ['status', 'Served']
                    ]);
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
