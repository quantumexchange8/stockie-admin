<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigPromotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "config_promotions";

    protected $fillable = [
        'title',
        'description',
        'promotion_from',
        'promotion_to',
        'image',
    ];
}
