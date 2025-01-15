<?php

namespace Database\Seeders;

use App\Models\MSICCodes;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MSICCodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MSICCodes::truncate();

        $json = File::get('database\seeders\data\MSICSubCategoryCodes.json');
        $codes = json_decode($json, true);

        foreach ($codes as $key => $code) {
            MSICCodes::create([
                'Code' => $code['Code'],
                'Description' => $code['Description'],
                'MSIC Category Reference' => $code['MSIC Category Reference']
            ]);
        }
    }
}
