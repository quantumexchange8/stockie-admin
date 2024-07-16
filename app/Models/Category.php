<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = "categories";

    protected $fillable = [
        'name',
    ];
    
    /**
     * IventoryModel
     * Get the inventories of the category.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Iventory::class, 'category_id');
    }
}
