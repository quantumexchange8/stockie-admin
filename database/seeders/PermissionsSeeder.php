<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard',
            'order-management',
            'shift-control',
            'shift-record',
            'menu-management',
            'inventory',
            'waiter',
            'customer',
            'table-room',
            'reservation',
            'transcation-listing',
            'e-invoice-submission',
            'loyalty-programme',
            'admin-user',
            'sales-analysis',
            'activity-logs',
            'configuration',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Delete any permissions that are not in the list
        Permission::whereNotIn('name', $permissions)->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
