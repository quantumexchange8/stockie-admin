<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WaiterAttendance;
use Illuminate\Console\Command;

class WaiterAutoCheckout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waiters:auto-checkout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically check out all waiters who are still checked in by 6 am of the day';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all waiters with active check-ins (no check-out record)
        $waiters = User::has('attendances')
                        ->with([
                            'latestAttendance' => function($query) {
                                $query->select(['id', 'user_id', 'check_in', 'status']);
                            },
                            'latestBreak:id,user_id,check_in,status'
                        ])
                        ->get(['id', 'full_name', 'role_id']);

        $processedWaiter = 0;

        foreach ($waiters as $waiter) {
            if ($this->shouldCheckout($waiter)) {
                $this->autoCheckoutWaiter($waiter);
                $processedWaiter++;
            }
        }

        $this->info("Auto-checkout completed. Processed $processedWaiter waiters.");
    }

    protected function shouldCheckout(User $waiter)
    {
        // If no attendance records at all
        if (!$waiter->latestAttendance) return false;

        // If already checked out
        if ($waiter->latestAttendance->status === 'Checked out') return false;

        return true;
    }

    protected function autoCheckoutWaiter(User $waiter)
    {
        try {
            $latestBreak = $waiter->latestBreak;

            if ($latestBreak && $latestBreak->status === 'Break start') {
                WaiterAttendance::create([
                    'user_id' => $waiter->id,
                    'check_in' => $latestBreak->check_in,
                    'check_out' => now(),
                    'status' => 'Break end'
                ]);
            }
            
            $latestAttendance = $waiter->latestAttendance;

            if ($latestAttendance) {
                // Create check-out record (matching your existing structure)
                WaiterAttendance::create([
                    'user_id' => $waiter->id,
                    'check_in' => $latestAttendance->check_in, // Match preceeding check-in
                    'check_out' => now(),
                    'status' => 'Checked out'
                ]);

                $this->info("Checked out waiter {$waiter->role_id} at ".now());
            }
        } catch (\Exception $e) {
            $this->error("Error checking out waiter {$waiter->role_id}: ".$e->getMessage());
        }
    }
}
