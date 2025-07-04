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
                            ->select('user_id')
                            ->whereNotNull('user_id')
                            // ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime')))
                            ->distinct()
                            ->get(['user_id'])
                            ->toArray();

        $autoUnlockSetting = Setting::where('name', 'Table Auto Unlock')
                                    ->first(['name', 'value_type', 'value']);

        $duration = $autoUnlockSetting->value_type === 'minutes'
            ? ((int)floor($autoUnlockSetting->value ?? 0)) * 60
            : ((int)floor($autoUnlockSetting->value ?? 0));

        Table::whereNotIn('locked_by', $activeUsers)
                ->orWhere(fn ($query) =>
                    $query->whereIn('locked_by', $activeUsers)
                        ->where('updated_at', '<=', now()->subSeconds($duration))
                )
                ->update([
                    'is_locked' => false,
                    'locked_by' => null,
                ]);
    }
}
