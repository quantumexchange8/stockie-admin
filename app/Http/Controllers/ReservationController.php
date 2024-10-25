<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderTableRequest;
use App\Http\Requests\ReservationRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderTable;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function index()
    {
        $cutoffTime = Carbon::now()->subHours(24);

        // $yesterday = now('Asia/Kuala_Lumpur')->subDay();
        $upcomingReservations = Reservation::with([
                                                'reservedFor.reservations', 
                                                'reservedFor.reservationCancelled', 
                                                'reservedFor.reservationAbandoned', 
                                                'reservedBy', 
                                                'handledBy'
                                            ])
                                            // ->whereDate('reservation_date', '>', $yesterday)
                                            ->where(function ($query) use ($cutoffTime) {
                                                // Check for 'Pending' status and overdue reservation_date
                                                $query->where('status', 'Pending')
                                                    ->where('reservation_date', '>=', $cutoffTime);
                                
                                                // Or check for 'Delayed' status and overdue action_date
                                                $query->orWhere(function ($subQuery) use ($cutoffTime) {
                                                    $subQuery->where('status', 'Delayed')
                                                        ->where('action_date', '>=', $cutoffTime);
                                                });
                                            })
                                            ->orderBy(DB::raw("CASE WHEN status = 'Delayed' THEN action_date ELSE reservation_date END"), 'asc')
                                            ->get();

        $waiters = User::where('role', 'waiter')
                        ->get(['id', 'full_name'])
                        ->map(function ($waiter) { 
                            return [
                                'text' => $waiter->full_name,
                                'value' => $waiter->id
                            ];
                        });

        return Inertia::render('Reservation/Reservation', [
            'reservations' => $upcomingReservations,
            'customers' => Customer::all(),
            'tables' => Table::orderBy('zone_id')->get(),
            'occupiedTables' => Table::where('status', '!=', 'Empty Seat')->get(),
            'waiters' => $waiters,
        ]);
    }
    
    /**
     * Store a new reservation.
     */
    public function store(ReservationRequest $request)
    {   
        $customer = is_int($request->name) ? Customer::find($request->name) : null;

        Reservation::create(attributes: [
            'reservation_no' => RunningNumberService::getID('reservation'),
            'customer_id' => $customer?->id ?? null,
            'name' => $customer?->name ?? $request->name,
            'pax' => $request->pax,
            'table_no' => $request->table_no,
            'phone' => $request->phone,
            'cancel_type' => '',
            'remark' => '',
            'status' => 'Pending',
            'reservation_date' => $request->reservation_date,
            'handled_by' => $request->reserved_by,
            'reserved_by' => $request->reserved_by,
        ]);

        return redirect()->back();
    }
    
    /**
     * Update reservation details.
     */
    public function update(ReservationRequest $request, string $id)
    {   
        $reservation = Reservation::find($id);

        $customer = is_int($request->name) ? Customer::find($request->name) : null;

        $reservation->update([
            'customer_id' => $customer?->id ?? null,
            'name' => $customer?->name ?? $request->name,
            'pax' => $request->pax,
            'table_no' => $request->table_no,
            'phone' => $request->phone,
            'cancel_type' => $reservation->cancel_type,
            'remark' => $reservation->remark,
            'status' => $reservation->status,
            'reservation_date' => $request->reservation_date,
            'handled_by' => $request->reserved_by,
            'reserved_by' => $request->reserved_by,
        ]);

        return redirect()->back();
    }
    
    /**
     * Check in guest to table based on reservation.
     */
    public function checkInGuest(Request $request, string $id)
    {
        // Validate form request
        $validatedData = $request->validate(
            [
                'assigned_waiter' => 'required|integer',
                'pax' => 'required|string',
                'tables' => 'required|array',
                'handled_by' => 'required|integer',
            ], 
            ['assigned_waiter.required' => 'This field is required.']
        );

        $reservation = Reservation::find($id);

        // Create new order
        $newOrder = Order::create([
            'order_no' => RunningNumberService::getID('order'),
            'pax' => $validatedData['pax'],
            'user_id' => $validatedData['assigned_waiter'],
            'amount' => 0.00,
            'total_amount' => 0.00,
            'status' => 'Pending Serve',
        ]);
        
        // Update selected tables and create order table records in a loop
        foreach ($validatedData['tables'] as $table_id) {
            $table = Table::find($table_id);
    
            // Update table status and related order
            $table->update([
                'status' => 'Pending Order',
                'order_id' => $newOrder->id
            ]);

            // Create new order table
            OrderTable::create([
                'table_id' => $table_id,
                'pax' => $validatedData['pax'],
                'user_id' => $validatedData['handled_by'],
                'status' => 'Pending Order',
                'order_id' => $newOrder->id
            ]);
        }
        
        // Update reservation details
        $reservation->update([
            'status' => 'Checked in',
            'action_date' => now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
            'order_id' => $newOrder->id,
            'handled_by' => $validatedData['handled_by'],
        ]);

        return redirect()->back();
    }
    
    /**
     * Update reservation status to 'No show'.
     */
    public function markAsNoShow(Request $request, string $id)
    {
        $reservation = Reservation::find($id);
        $reservation->update([
            'status' => 'No show'
        ]);
        
        return redirect()->back();
    }
    
    /**
     * Delay reservation.
     */
    public function delayReservation(Request $request, string $id)
    {
        // Validate form request
        $validatedData = $request->validate(
            [
                'new_reservation_date' => 'required|date_format:Y-m-d H:i:s',
                'handled_by' => 'required|integer',
            ], 
            ['required' => 'This field is required.']
        );

        $reservation = Reservation::find($id);
        $reservation->update([
            'action_date' => $validatedData['new_reservation_date'],
            'handled_by' => $validatedData['handled_by'],
            'status' => 'Delayed',
        ]);
        
        return redirect()->back();
    }

    /**
     * Cancel reservation.
     */
    public function cancelReservation(Request $request, string $id)
    {
        // Validate form request
        $validatedData = $request->validate(
            [
                'cancel_type' => 'required|string',
                'remark' => 'required|string',
                'handled_by' => 'required|integer',
            ], 
            ['required' => 'This field is required.']
        );

        $reservation = Reservation::find($id);
        $reservation->update([
            'cancel_type' => $validatedData['cancel_type'],
            'remark' => $validatedData['remark'],
            'handled_by' => $validatedData['handled_by'],
            'status' => 'Cancelled',
        ]);
        
        return redirect()->back();
    }

    /**
     * Get reservations.
     */
    public function getReservations()
    {
        $cutoffTime = Carbon::now()->subHours(24);
        // $yesterday = now('Asia/Kuala_Lumpur')->subDay();
        $upcomingReservations = Reservation::with([
                                                'reservedFor.reservations', 
                                                'reservedFor.reservationCancelled', 
                                                'reservedFor.reservationAbandoned', 
                                                'reservedBy', 
                                                'handledBy'
                                            ])
                                            ->where(function ($query) use ($cutoffTime) {
                                                // Check for 'Pending' status and overdue reservation_date
                                                $query->where('status', 'Pending')
                                                    ->where('reservation_date', '>=', $cutoffTime);
                                
                                                // Or check for 'Delayed' status and overdue action_date
                                                $query->orWhere(function ($subQuery) use ($cutoffTime) {
                                                    $subQuery->where('status', 'Delayed')
                                                        ->where('action_date', '>=', $cutoffTime);
                                                });
                                            })
                                            ->orderBy(DB::raw("CASE WHEN status = 'Delayed' THEN action_date ELSE reservation_date END"), 'asc')
                                            ->get();

        return response()->json($upcomingReservations);
    }

    /**
     * View the history list of reservations.
     */
    public function viewReservationHistory()
    {
        // $yesterday = now('Asia/Kuala_Lumpur')->subDay();
        $pastReservations = Reservation::with([
                                                'reservedFor.reservations', 
                                                'reservedFor.reservationCancelled', 
                                                'reservedFor.reservationAbandoned', 
                                                'reservedBy', 
                                                'handledBy'
                                            ])
                                            // ->whereDate('reservation_date', '<', $yesterday)
                                            ->whereIn('status', ['Checked in', 'Completed', 'No show', 'Cancelled'])
                                            ->orderBy('id')
                                            ->get();

        return Inertia::render('Reservation/Partials/ViewReservationHistory', [
            'reservations' => $pastReservations,
            'tables' => Table::all()
        ]);
    }
    
    /**
     * Delete reservation.
     */
    public function delete(string $id)
    {
        $reservation = Reservation::find($id);
        if ($reservation) $reservation->delete();
        
        return redirect()->back();
    }
}
