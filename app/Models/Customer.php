<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, LogsActivity;

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
        'total_spending'
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Ranking::class,'ranking');
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

    /**
     * Get the point histories of the customer.
     */
    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class, 'customer_id');
    }

    /**
     * Get the ranking rewards of the customer.
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(CustomerReward::class, 'customer_id');
    }
}
