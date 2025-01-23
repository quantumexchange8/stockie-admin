<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Iventory extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, LogsActivity;
    
    protected $table = "iventories";

    protected $fillable = [
        'name',
        // 'category_id',
        'image',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
    
    /**
     * IventoryItem Model
     * Get the inventory items of the inventory.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(IventoryItem::class, 'inventory_id', 'id');
    }
    
    /**
     * Category Model
     * Get the category of the inventory.
     */
    // public function category(): BelongsTo
    // {
    //     return $this->belongsTo(Category::class, 'category_id');
    // }

    /**
     * StockHistory Model
     * Get the stock histories of the inventory..
     */
    public function stockHistories(): HasMany
    {
        return $this->hasMany(StockHistory::class, 'inventory_id', 'id');
    }
}
