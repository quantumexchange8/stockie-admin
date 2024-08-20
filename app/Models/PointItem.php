<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "point_items";

    protected $fillable = [
        'point_id',
        'inventory_item_id',
        'item_qty',
    ];
    
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
