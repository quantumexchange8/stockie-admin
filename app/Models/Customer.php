<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "customers";

    protected $fillable = [
        'name',
        'full_name',
        'email',
        'phone',
        'password',
        'ranking',
        'role',
        'point',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
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

    public function rankings(): BelongsTo
    {
        return $this->belongsTo(Ranking::class,'ranking', 'id');
    }

    public function keepItems(): HasMany
    {
        return $this->hasMany(KeepItem::class,'customer_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id');
    }

    public function reservationCancelled(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id')->where('status', 'Cancelled');
    }

    public function reservationAbandoned(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id')->where('status', 'No show');
    }

    /**
     * Get the user that handled the payment.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'customer_id');
    }
}
