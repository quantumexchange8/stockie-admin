<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class ClearNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'general:clear-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes notification that are more than three months';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        auth()->user()->notifications()
                        ->where('created_at', '<',Carbon::now()->subMonths(3))
                        ->delete();

        $this->info('Notifications that are more than three months old cleared');
    }
}
