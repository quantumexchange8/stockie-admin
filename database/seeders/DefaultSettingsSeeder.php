<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ItemCategory;
use App\Models\PayoutConfig;
use App\Models\RunningNumber;
use App\Models\Setting;
use App\Models\WaiterPosition;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Item Categories (inventory item unit)
        $defaultItemCategories = [
            ['type'=> 'Bottle', 'name' => 'Bottle', 'low_stock_qty' => 25],
            ['type'=> 'Can', 'name' => 'Can', 'low_stock_qty' => 25],
            ['type'=> 'Pint', 'name' => 'Pint', 'low_stock_qty' => 25],
            ['type'=> 'Tower', 'name' => 'Tower', 'low_stock_qty' => 25],
            ['type'=> 'Jug', 'name' => 'Jug', 'low_stock_qty' => 25],
        ];

        foreach ($defaultItemCategories as $itemCategory) {
            ItemCategory::firstOrCreate(
                ['type' => $itemCategory['type']], // unique check condition
                $itemCategory                      // values to set if not found
            );
        }

        // Default Categories (product categories)
        $defaultCategories = [
            ['name'=> 'Beer', 'keep_type' => 'qty'],
            ['name'=> 'Liquor', 'keep_type' => 'all'],
            ['name'=> 'Others', 'keep_type' => 'qty'],
            ['name'=> 'Food', 'keep_type' => 'qty'],
        ];

        foreach ($defaultCategories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']], // unique check condition
                $category                      // values to set if not found
            );
        }

        // // Default Payout Configs (for e-invoice settings)
        // $defaultPayoutConfigs = [
        //     ['merchant_id'=> 1, 'api_key' => 'bia5B0thBOUTHtfgz9lMEu9wMzoVPnRzCNQCPkFw', 'url'=> 'https://ct-einvoice.currenttech.pro/'],
        // ];

        // foreach ($defaultPayoutConfigs as $payoutConfig) {
        //     PayoutConfig::firstOrCreate(
        //         ['merchant_id' => $payoutConfig['merchant_id']], // unique check condition
        //         $payoutConfig                      // values to set if not found
        //     );
        // }

        // Default Running Numbers
        $defaultRunningNumbers = [
            ['type'=> 'reservation', 'prefix' => 'R', 'digits' => 6, 'last_number' => 0],
            ['type'=> 'order', 'prefix' => 'M', 'digits' => 4, 'last_number' => 0],
            ['type'=> 'payment', 'prefix' => 'RCPT', 'digits' => 8, 'last_number' => 0],
            ['type'=> 'customer', 'prefix' => 'CID', 'digits' => 6, 'last_number' => 0],
            ['type'=> 'refund', 'prefix' => 'RFD', 'digits' => 5, 'last_number' => 0],
            ['type'=> 'shift', 'prefix' => null, 'digits' => null, 'last_number' => 0],
            ['type'=> 'consolidate', 'prefix' => 'CSLD', 'digits' => 6, 'last_number' => 0],
        ];

        foreach ($defaultRunningNumbers as $runningNumber) {
            RunningNumber::firstOrCreate(
                ['type' => $runningNumber['type']], // unique check condition
                $runningNumber                      // values to set if not found
            );
        }

        // Default Settings
        $defaultSettings = [
            ['name'=> 'SST', 'type' => 'tax', 'value_type' => 'percentage', 'value' => 10.00, 'point' => null],
            ['name'=> 'Service Tax', 'type' => 'tax', 'value_type' => 'percentage', 'value' => 6.00, 'point' => null],
            ['name'=> 'Point', 'type' => 'point', 'value_type' => 'price', 'value' => 1.00, 'point' => 1],
            ['name'=> 'Recurring Day', 'type' => 'date', 'value_type' => 'day', 'value' => 1.00, 'point' => null],
            ['name'=> 'Table Auto Unlock', 'type' => 'lock_timer', 'value_type' => 'seconds', 'value' => 10.00, 'point' => null],
            ['name'=> 'Point Expiration', 'type' => 'expiration', 'value_type' => 'day', 'value' => 7.00, 'point' => null],
            ['name'=> 'Point Expiration Notification', 'type' => 'expiration', 'value_type' => 'day', 'value' => 7.00, 'point' => null],
            ['name'=> 'Cut Off Time', 'type' => 'timer', 'value_type' => 'time', 'value' => 6.00, 'point' => null],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::firstOrCreate(
                ['name' => $setting['name']], // unique check condition
                $setting                      // values to set if not found
            );
        }

        // Default Waiter Positions
        $defaultWaiterPositions = [
            ['name'=> 'waiter'],
            ['name'=> 'captain'],
        ];

        foreach ($defaultWaiterPositions as $position) {
            WaiterPosition::firstOrCreate(
                ['name' => $position['name']], // unique check condition
                $position                      // values to set if not found
            );
        }

    }
}
