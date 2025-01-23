<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ItemCategory extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = "item_categories";

    protected $fillable = [
        'type',
        'name',
        'low_stock_qty',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    /**
     * IventoryItem Model
     * Get the inventory items that has this item category.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(IventoryItem::class, 'item_cat_id');
    }
}
