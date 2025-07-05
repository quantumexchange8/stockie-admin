<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Table;
use DB;
use Illuminate\Console\Command;

class ResetLockedTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tables:reset-locked-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for tables that are locked but not viewed by the respective user that locked it and unlock them.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $activeUsers = DB::table('sessions')
                            ->whereNotNull('user_id')
                            ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime')))
                            ->pluck('user_id')
                            ->toArray();

        $autoUnlockSetting = Setting::where('name', 'Table Auto Unlock')
                                    ->first(['name', 'value_type', 'value']);

        $duration = $autoUnlockSetting->value_type === 'minutes'
            ? ((int)floor($autoUnlockSetting->value ?? 0)) * 60
            : ((int)floor($autoUnlockSetting->value ?? 0));

        Table::where(function($query) use ($activeUsers, $duration) {
                    // Tables locked by inactive users
                    $query->whereNotIn('locked_by', $activeUsers)
                        ->whereNotNull('locked_by');
                })
                ->orWhere(function($query) use ($duration) {
                    // Tables locked longer than duration
                    $query->where('updated_at', '<=', now()->subSeconds($duration))
                        ->where('is_locked', true);
                })
                ->update([
                    'is_locked' => false,
                    'locked_by' => null,
                    'updated_at' => now()
                ]);

        $this->info('Successfully reset locked tables.');
    }
}
