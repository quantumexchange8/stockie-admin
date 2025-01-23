<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PointItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = "point_items";

    protected $fillable = [
        'point_id',
        'inventory_item_id',
        'item_qty',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
    
    /**
     * Point Model
     * Get the point(redeemable item) of the point item.
     */
    public function point(): BelongsTo
    {
        return $this->belongsTo(Point::class, 'point_id');
    }
    
    /**
     * IventoryItem Model
     * Get the inventory item of the point item.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(IventoryItem::class, 'inventory_item_id');
    }
}
