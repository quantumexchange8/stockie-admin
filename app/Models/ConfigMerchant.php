<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ConfigMerchant extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'merchant_name',
        'tin_no',
        'registration_no',
        'msic_code',
        'merchant_contact',
        'email_address',
        'sst_registration_no',
        'description',
        'classification_code',
        'merchant_address_line1',
        'merchant_address_line2',
        'postal_code',
        'area',
        'state',
        'merchant_image'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function classificationCode(): BelongsTo
    {
        return $this->belongsTo(ClassificationCode::class, 'classification_code');
    }

    public function msicCode(): BelongsTo
    {
        return $this->belongsTo(MSICCodes::class, 'msic_code');
    }
}
