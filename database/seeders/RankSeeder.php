<?php

namespace Database\Seeders;

use App\Models\Ranking;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultRank = Ranking::firstOrCreate([
            'name'=> 'Member',
            'min_amount' => 0,
            'reward' => 'inactive',
            'icon' => ''
        ]);

        // Get the path to the favicon
        $iconPath = public_path('favicon.ico');

        // Check if the file exists
        if (File::exists($iconPath)) {
            $defaultRank->addMedia($iconPath)
                        ->preservingOriginal()
                        ->toMediaCollection('ranking');
        }
    }
}
