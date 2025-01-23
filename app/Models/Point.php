<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Point extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, LogsActivity;

    protected $table = "points";

    protected $fillable = [
        'name',
        'point',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
    
    /**
     * PointItem Model
     * Get the items of the point(redeemable item).
     */
    public function pointItems(): HasMany
    {
        return $this->hasMany(PointItem::class, 'point_id');
    }
    
    /**
     * PointHistory Model
     * Get the histories of the point(redeemable item).
     */
    // public function pointHistories(): HasMany
    // {
    //     return $this->hasMany(PointHistory::class, 'point_id');
    // }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class,'product_id');
    }
}
