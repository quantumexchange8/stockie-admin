<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:clear-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired sessions from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lifetimeMinutes = config('session.lifetime');
        $expirationTime = now()->subMinutes($lifetimeMinutes)->getTimestamp();
        
        DB::table('sessions')
            ->where('last_activity', '<=', $expirationTime)
            ->orWhere('user_id', '=', null)
            ->delete();
        
        $this->info('Expired sessions pruned successfully.');
    }
}
