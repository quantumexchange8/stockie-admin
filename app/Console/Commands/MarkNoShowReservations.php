<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkNoShowReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:mark-no-show';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks reservations as "No Show" if they have passed the 24-hour mark after the reservation date.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoffTime = Carbon::now()->subHours(24);
    
        // Check if there are any matching reservations before running the update
        $reservationCount = Reservation::where(function ($query) use ($cutoffTime) {
                // Check for 'Pending' status and overdue reservation_date
                $query->where('status', 'Pending')
                    ->where('reservation_date', '<=', $cutoffTime);

                // Or check for 'Delayed' status and overdue action_date
                $query->orWhere(function ($subQuery) use ($cutoffTime) {
                    $subQuery->where('status', 'Delayed')
                        ->where('action_date', '<=', $cutoffTime);
                });
            })
            ->count();
    
        if ($reservationCount === 0) {
            $this->info("No reservations to mark as No show.");
            return;
        }
    
        // Perform the bulk update
        $affectedRows = Reservation::where(function ($query) use ($cutoffTime) {
                // Check for 'Pending' status and overdue reservation_date
                $query->where('status', 'Pending')
                    ->where('reservation_date', '<=', $cutoffTime);

                // Or check for 'Delayed' status and overdue action_date
                $query->orWhere(function ($subQuery) use ($cutoffTime) {
                    $subQuery->where('status', 'Delayed')
                        ->where('action_date', '<=', $cutoffTime);
                });
            })
            ->update(['status' => 'No show']);

        $this->info("Successfully marked {$affectedRows} reservations as No show.");
    }
}
