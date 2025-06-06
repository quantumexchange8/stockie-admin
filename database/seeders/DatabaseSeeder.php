<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(PermissionsSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(RankSeeder::class);
        $this->call(DefaultSettingsSeeder::class);
        $this->call(ClassificationCodesSeeder::class);
        $this->call(MSICCodesSeeder::class);
        $this->call(StateSeeder::class);
    }
}
