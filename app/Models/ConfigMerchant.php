<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigMerchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_name',
        'merchant_contact',
        'merchant_address',
    ];
}
