<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name','email', 'password',  'phone', 'role_id', 'salary', 'worker_email', 'full_name','role'
        
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

    /**
     * PointHistory Model
     * Get the histories of the point(redeemable item) redeemed by the user.
     */
    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class, 'redeem_by');
    }

    /**
     * OrderItem Model
     * Get the order items ordered by the user.
     */
    public function orderedItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'user_id');
    }

    public function keepItems () : HasMany
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

    public function attendances () : HasMany
    {
        return $this->hasMany(WaiterAttendance::class, 'user_id');
    }

    public function configIncentEmployee () : HasMany
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
