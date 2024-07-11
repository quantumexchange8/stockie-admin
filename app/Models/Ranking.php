<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class  Ranking extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'min_amount',
        'reward',
        'icon',
    ];


    public function rankingRewards(): HasMany
    {
        return $this->hasMany(RankingReward::class, 'ranking_id');
    }
    public function customer(): HasMany
    {
        return $this->hasMany(Customer::class, 'ranking');
    }
}
