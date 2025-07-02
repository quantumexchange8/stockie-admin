<?php

namespace App\Console\Commands;

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

        Table::whereNotIn('locked_by', $activeUsers)
                ->update([
                    'is_locked' => false,
                    'locked_by' => null,
                ]);
    }
}
