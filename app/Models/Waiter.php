<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Waiter extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name', 'phone', 'email', 'staffid', 'salary', 'stockie_email', 'stockie_password'
    ];

    public function keepItems () : HasMany
    {
        return $this->hasMany(KeepItem::class);
    }
}
