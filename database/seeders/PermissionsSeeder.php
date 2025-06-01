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
            'all-report',
            'inventory',
            'waiter',
            'customer',
            'table-room',
            'reservation',
            'transaction-listing',
            'einvoice-submission',
            'loyalty-programme',
            'admin-user',
            'sales-analysis',
            'activity-logs',
            'configuration',
            'free-up-table',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Delete any permissions that are not in the list
        Permission::whereNotIn('name', $permissions)->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
