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
use App\Notifications\OrderAssignedWaiter;
use App\Notifications\OrderCheckInCustomer;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

                                                // Or check for 'Checked in' status
                                                $query->orWhere(function($subQuery) use ($cutoffTime){
                                                    $subQuery->where('status', 'Checked in')
                                                            ->where('action_date', '>=', $cutoffTime);
                                                });
                                            })
                                            ->orderBy(DB::raw("CASE WHEN status = 'Delayed' THEN action_date ELSE reservation_date END"), 'asc')
                                            ->get()
                                            ->map(function ($res) {
                                                $tableData = $res->table_no; 
                                                
                                                if (is_array($tableData)) {
                                                    $res['merged_table_no'] = collect($tableData)
                                                        ->pluck('name') 
                                                        ->implode(', '); 
                                                } else {
                                                    $res['merged_table_no'] = $tableData; 
                                                }

                                                return $res;
                                            });            

        $upcomingReservations->each(function ($upcomingReservation){
            if($upcomingReservation->reservedFor){
                $upcomingReservation->reservedFor->image = $upcomingReservation->reservedFor->getFirstMediaUrl('customer');
            }
        });

        $waiters = User::where('position', 'waiter')
                        ->get(['id', 'full_name'])
                        ->map(function ($waiter) { 
                            return [
                                'text' => $waiter->full_name,
                                'value' => $waiter->id,
                                'image' => $waiter->getFirstMediaUrl('user'),
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

        $newReservation = Reservation::create(attributes: [
            'reservation_no' => RunningNumberService::getID('reservation'),
            'customer_id' => $customer?->id ?? null,
            'name' => $customer?->full_name ?? $request->name,
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

        activity()->useLog('create-reservation')
                    ->performedOn($newReservation)
                    ->event('added')
                    ->withProperties([
                        'created_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'reserved_by' => $newReservation->name,
                        'reservation_date' => $newReservation->reservation_date,
                    ])
                    ->log("Reservation for $newReservation->name on $newReservation->reservation_date is added.");

        return redirect()->back();
    }
    
    /**
     * Update reservation details.
     */
    public function update(ReservationRequest $request, string $id)
    {   
        $reservation = Reservation::with([
                                        'reservedFor:id', 
                                        'reservedFor.reservations:id', 
                                        'reservedFor.reservationCancelled', 
                                        'reservedFor.reservationAbandoned', 
                                        'reservedBy:id,full_name', 
                                        'handledBy:id,full_name'
                                    ])->find($id);

        if($reservation->reservedFor){
            $reservation->reservedFor->image = $reservation->reservedFor->getFirstMediaUrl('customer');
        }


        $customer = is_int($request->name) ? Customer::find($request->name) : null;

        $reservation->update([
            'customer_id' => $customer?->id ?? null,
            'name' => $customer?->full_name ?? $request->name,
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
        
        activity()->useLog('edit-reservation-detail')
                    ->performedOn($reservation)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'reserved_for' => $reservation->name,
                        'reservation_date' => $reservation->reservation_date,
                    ])
                    ->log("Reservation detail for $reservation->name on $reservation->reservation_date is updated.");

        return response()->json($reservation);
        // return redirect()->back();
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
            'customer_id' => $reservation->customer_id ?? null,
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

        //Check in activity log and notification
        $waiter = User::select('full_name', 'id')->find($validatedData['assigned_waiter']);
        $waiter->image = $waiter->getFirstMediaUrl('user');

        $orderTables = implode(', ', array_map(function ($table) {
            return Table::where('id', $table)->pluck('table_no')->first();
        }, $validatedData['tables']));

        activity()->useLog('Order')
                ->performedOn($newOrder)
                ->event('check in')
                ->withProperties([
                    'waiter_name' => $waiter->full_name,
                    'table_name' => $orderTables, 
                    'waiter_image' => $waiter->image
                ])
                ->log("New customer check-in by :properties.waiter_name.");
        
        Notification::send(User::all(), new OrderCheckInCustomer($orderTables, $waiter->full_name, $waiter->id));

        //Assigned activity log and notification
        activity()->useLog('Order')
                    ->performedOn($newOrder)
                    ->event('assign to serve')
                    ->withProperties([
                        'waiter_name' => $waiter->full_name,
                        'waiter_image' => $waiter->image,
                        'table_name' => $orderTables, 
                        'assigned_by' => auth()->user()->full_name,
                        'assigner_image' => auth()->user()->getFirstMediaUrl('user'),
                    ])
                    ->log("Assigned :properties.waiter_name to serve :properties.table_name.");

        Notification::send(User::all(), new OrderAssignedWaiter($orderTables, auth()->user()->id, $waiter->id));



        
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

        activity()->useLog('mark-as-no-show')
                    ->performedOn($reservation)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'reserved_for' => $reservation->name,
                        'reservation_date' => $reservation->reservation_date,
                    ])
                    ->log("Reservation for $reservation->name on $reservation->reservation_date is marked as no show.");
        
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

        activity()->useLog('delay-reservation')
                    ->performedOn($reservation)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'reserved_for' => $reservation->name,
                        'reservation_date' => $reservation->reservation_date,
                    ])
                    ->log("Reservation for $reservation->name on $reservation->reservation_date is delayed.");
        
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

        activity()->useLog('cancel-reservation')
                    ->performedOn($reservation)
                    ->event('cancelled')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'reserved_for' => $reservation->name,
                        'reservation_date' => $reservation->reservation_date,
                    ])
                    ->log("Reservation for $reservation->name on $reservation->reservation_date is cancelled.");
        
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

                                                // Or check for 'Checked in' status
                                                $query->orWhere(function($subQuery) use ($cutoffTime){
                                                    $subQuery->where('status', 'Checked in')
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
                                            ->whereDate('reservation_date', '>', Carbon::now()->timezone('Asia/Kuala_Lumpur')->subMonth())
                                            ->whereIn('status', ['Completed', 'No show', 'Cancelled'])
                                            ->orderBy('id')
                                            ->get();

        $pastReservations->each(function ($pastReservation){
            if($pastReservation->reservedFor)
            {
                $pastReservation->reservedFor->image = $pastReservation->reservedFor->getFirstMediaUrl('customer');
            }
        });

        return Inertia::render('Reservation/Partials/ViewReservationHistory', [
            'reservations' => $pastReservations,
            'tables' => Table::all()
        ]);
    }

    /**
     * Filter reservation history with date filter.
     */
    public function filterReservationHistory(Request $request) 
    {
        $dateFilter = $request->input('date_filter');
        $status = $request->input('checkedFilters.status') ?? ['Completed', 'No show', 'Cancelled'];
        $dateFilter = array_map(function ($date){
            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d H:i:s');
        }, $dateFilter);

        $data = Reservation::whereDate('reservation_date', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0])
                            ->when(count($dateFilter) > 1, function($subQuery) use ($dateFilter) {
                                $subQuery->whereDate('reservation_date', '<=', $dateFilter[1]);
                            })
                            ->with([
                                'reservedFor.reservations', 
                                'reservedFor.reservationCancelled', 
                                'reservedFor.reservationAbandoned', 
                                'reservedBy', 
                                'handledBy'
                            ])
                            // ->whereDate('reservation_date', '<', $yesterday)
                            ->whereIn('status', $status)
                            ->orderBy('id')
                            ->get();

        $data->each(function ($pastReservation){
            if($pastReservation->reservedFor)
            {
                $pastReservation->reservedFor->image = $pastReservation->reservedFor->getFirstMediaUrl('customer');
            }
        });

        return response()->json($data);

        // $dateFilter = $request->input('date_filter');
        // $query = Reservation::query();

        // if ($dateFilter && is_array($dateFilter)) {
        //     //Single date filter
        //     if (count($dateFilter) === 1) {
        //         $date = Carbon::parse($dateFilter[0])->timezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
        //         $query->whereDate('reservation_date', $date);
        //     }

        //     //Range date filter
        //     if (count($dateFilter) > 1) {
        //         $startDate = Carbon::parse($dateFilter[0])->timezone('Asia/Kuala_Kumpur')->format('Y-m-d H:i:s');
        //         $endDate = Carbon::parse($dateFilter[1])->timezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
        //         $query->whereDate('reservation_date', '>=', $startDate)
        //                 ->whereDate('reservation_date', '<=', $endDate);
        //     }
        // }


        // $data = $query->with([
        //                     'reservedFor.reservations', 
        //                     'reservedFor.reservationCancelled', 
        //                     'reservedFor.reservationAbandoned', 
        //                     'reservedBy', 
        //                     'handledBy'
        //                 ])
        //                 // ->whereDate('reservation_date', '<', $yesterday)
        //                 ->whereIn('status', ['Completed', 'No show', 'Cancelled'])
        //                 ->orderBy('id')
        //                 ->get();

        // $data->each(function ($pastReservation){
        //     if($pastReservation->reservedFor)
        //     {
        //         $pastReservation->reservedFor->image = $pastReservation->reservedFor->getFirstMediaUrl('customer');
        //     }
        // });


        // return response()->json($data);
    }
    
    /**
     * Delete reservation.
     */
    public function delete(string $id)
    {
        $reservation = Reservation::find($id);

        activity()->useLog('delete-reservation')
                    ->performedOn($reservation)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'reserved_for' => $reservation->name,
                        'reservation_date' => $reservation->reservation_date,
                    ])
                    ->log("Reservation for $reservation->name on $reservation->reservation_date is deleted.");

        if ($reservation) $reservation->delete();
        
        return redirect()->back();
    }
}
