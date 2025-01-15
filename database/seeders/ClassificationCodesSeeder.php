<?php

namespace Database\Seeders;

use App\Models\ClassificationCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ClassificationCodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClassificationCode::truncate();

        $json = File::get('database\seeders\data\ClassificationCodes.json');
        $codes = json_decode($json);

        foreach ($codes as $key => $code) {
            ClassificationCode::create([
                'code' => $code->Code,
                'description' => $code->Description,
            ]);
        }
    }
}
