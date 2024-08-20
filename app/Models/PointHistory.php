<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "point_histories";

    protected $fillable = [
        'point_id',
        'qty',
        'redeem_by',
        'redemption_date'
    ];
    
    /**
     * Point Model
     * Get the point(redeemable item) of the point history record.
     */
    public function point(): BelongsTo
    {
        return $this->belongsTo(Point::class, 'point_id');
    }
    
    /**
     * User Model
     * Get the user that redeemed the item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeem_by');
    }
}
