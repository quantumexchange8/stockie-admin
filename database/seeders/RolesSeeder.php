<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Super Admin',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // $user = User::firstOrCreate([
        //     'full_name' => 'Admin',
        //     'name' => 'admin',
        //     'email' => 'admin@admin.com',
        //     'password' => Hash::make('Test1234.'),
        // ]);
        
        // if (!$user->hasRole('Super Admin')) {
        //     $user->assignRole('Super Admin');
        // }
        User::find(1)->assignRole('Super Admin');

        // Delete any roles that are not in the list
        Role::whereNotIn('name', $roles)->delete();

    }
}
