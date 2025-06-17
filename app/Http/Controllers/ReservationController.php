<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderTableRequest;
use App\Http\Requests\ReservationRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderTable;
use App\Models\Reservation;
use App\Models\ShiftTransaction;
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
        $upcomingReservations = Reservation::where(function ($query) {
                                                $startDate = now()->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s');

                                                $query->where(fn ($q) =>
                                                            $q->whereNotNull('action_date')
                                                                ->whereDate('action_date', '>=', $startDate)
                                                        )
                                                        ->orWhere(fn ($q) =>
                                                            $q->whereNull('action_date')
                                                                ->whereDate('reservation_date', '>=', $startDate)
                                                        );
                                            })
                                            ->with([
                                                'reservedFor.reservations', 
                                                'reservedFor.reservationCancelled', 
                                                'reservedFor.reservationAbandoned', 
                                                'reservedBy', 
                                                'handledBy'
                                            ])
                                            ->whereIn('status', ['Pending', 'Delayed', 'Checked in'])
                                            ->orderBy(DB::raw("CASE WHEN status = 'Delayed' THEN action_date ELSE reservation_date END"), 'asc')
                                            ->get()
                                            ->map(function ($res) {
                                                $tableData = $res->table_no; 
                                                
                                                $res['merged_table_no'] = is_array($tableData)
                                                        ? collect($tableData)->pluck('name')->implode(', ') 
                                                        : $tableData;

                                                return $res;
                                            });            

        $upcomingReservations->each(function ($upcomingReservation){
            if($upcomingReservation->reservedFor){
                $upcomingReservation->reservedFor->image = $upcomingReservation->reservedFor->getFirstMediaUrl('customer');
            }
        });

        $waiters = User::where([
                            ['position', 'waiter'],
                            ['status', 'Active']
                        ])
                        ->get(['id', 'full_name'])
                        ->map(function ($waiter) { 
                            return [
                                'text' => $waiter->full_name,
                                'value' => $waiter->id,
                                'image' => $waiter->getFirstMediaUrl('user'),
                            ];
                        });

        $customers = Customer::where(function ($query) {
                                    $query->where('status', '!=', 'void')
                                        ->orWhereNull('status'); // Handle NULL cases
                                })
                                ->get();

        return Inertia::render('Reservation/Reservation', [
            'reservations' => $upcomingReservations,
            'customers' => $customers,
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
            'grace_period' => $request->grace_period,
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
            'grace_period' => $request->grace_period,
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

        $tableData = $reservation->table_no; 
                                    
        $reservation['merged_table_no'] = is_array($tableData)
                ? collect($tableData)->pluck('name')->implode(', ') 
                : $tableData;

        return response()->json($reservation);
    }
    
    /**
     * Check in guest to table based on reservation.
     */
    public function checkInGuest(Request $request, string $id)
    {
        return DB::transaction(function () use ($request, $id) {
            // Validate form request
            $validatedData = $request->validate(
                [
                    'assigned_waiter' => 'required|integer',
                    'pax' => 'required|string',
                    'tables' => 'required|array',
                    'handled_by' => 'required|integer',
                ], 
                ['required' => 'This field is required.']
            );

            $hasOpenedShift = ShiftTransaction::hasOpenedShift();

            if (!$hasOpenedShift) {
                return redirect()->back()->withErrors([
                    'summary' => 'No shift has been opened yet',
                    'detail' => "You'll need to open a shift before you can place order"
                ]);
            }

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
            foreach ($validatedData['tables'] as $table) {
                $table = Table::find($table['id']);
        
                // Update table status and related order
                $table->update([
                    'status' => 'Pending Order',
                    'order_id' => $newOrder->id
                ]);

                // Create new order table
                OrderTable::create([
                    'table_id' => $table['id'],
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

            $toBeNotified = $waiter->id === auth()->user()->id
                ? User::where('position', 'admin')->get()
                : User::where('position', 'admin')
                        ->orWhere('id', $waiter->id)
                        ->get();
            
            Notification::send($toBeNotified, new OrderCheckInCustomer($orderTables, $waiter->full_name, $waiter->id));

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

            Notification::send($toBeNotified, new OrderAssignedWaiter($orderTables, auth()->user()->id, $waiter->id));

            // Update reservation details
            $reservation->update([
                'status' => 'Checked in',
                'action_date' => now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                'order_id' => $newOrder->id,
                'handled_by' => $validatedData['handled_by'],
            ]);

            return redirect()->back();
        });
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
        $upcomingReservations = Reservation::where(function ($query) {
                                                $startDate = now()->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s');

                                                $query->where(fn ($q) =>
                                                            $q->whereNotNull('action_date')
                                                                ->whereDate('action_date', '>=', $startDate)
                                                        )
                                                        ->orWhere(fn ($q) =>
                                                            $q->whereNull('action_date')
                                                                ->whereDate('reservation_date', '>=', $startDate)
                                                        );
                                            })
                                            ->with([
                                                'reservedFor.reservations', 
                                                'reservedFor.reservationCancelled', 
                                                'reservedFor.reservationAbandoned', 
                                                'reservedBy', 
                                                'handledBy'
                                            ])
                                            ->whereIn('status', ['Pending', 'Delayed', 'Checked in'])
                                            ->orderBy(DB::raw("CASE WHEN status = 'Delayed' THEN action_date ELSE reservation_date END"), 'asc')
                                            ->get()
                                            ->map(function ($res) {
                                                $tableData = $res->table_no; 
                                                
                                                $res['merged_table_no'] = is_array($tableData)
                                                        ? collect($tableData)->pluck('name')->implode(', ') 
                                                        : $tableData;

                                                return $res;
                                            });  

        return response()->json($upcomingReservations);
    }

    /**
     * View the history list of reservations.
     */
    public function viewReservationHistory()
    {
        $dateFilter = [
            now()->subMonths(1)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
            now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
        ];

        $pastReservations = Reservation::where(function ($query) use ($dateFilter) {
                                            $startDate = Carbon::parse($dateFilter[0])->startOfDay()->format('Y-m-d H:i:s');
                                            $endDate = Carbon::parse($dateFilter[1])->endOfDay()->format('Y-m-d H:i:s');

                                            $query->where(fn ($q) =>
                                                        $q->whereNotNull('action_date')
                                                            ->whereDate('action_date', '>=', $startDate)
                                                            ->whereDate('action_date', '<=', $endDate)
                                                    )
                                                    ->orWhere(fn ($q) =>
                                                        $q->whereNull('action_date')
                                                            ->whereDate('reservation_date', '>=', $startDate)
                                                            ->whereDate('reservation_date', '<=', $endDate)
                                                    );
                                        })
                                        ->with([
                                            'reservedFor.reservations', 
                                            'reservedFor.reservationCancelled', 
                                            'reservedFor.reservationAbandoned', 
                                            'reservedBy', 
                                            'handledBy'
                                        ])
                                        ->whereIn('status', ['Completed', 'No show', 'Cancelled'])
                                        ->orderBy('id')
                                        ->get();

        $pastReservations->each(function ($pastReservation){
            if ($pastReservation->reservedFor) {
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
            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
            // return Carbon::parse($date, 'Asia/Kuala_Lumpur')->format('Y-m-d');
        }, $dateFilter);

        $data = Reservation::where(function ($query) use ($dateFilter) {
                                $startDate = Carbon::parse($dateFilter[0])->startOfDay()->format('Y-m-d H:i:s');

                                // 2 dates condition
                                if (count($dateFilter) > 1) {
                                    $endDate = Carbon::parse($dateFilter[1])->endOfDay()->format('Y-m-d H:i:s');

                                    $query->where(fn ($q) =>
                                                $q->whereNotNull('action_date')
                                                    ->whereDate('action_date', '>=', $startDate)
                                                    ->whereDate('action_date', '<=', $endDate)
                                            )
                                            ->orWhere(fn ($q) =>
                                                $q->whereNull('action_date')
                                                    ->whereDate('reservation_date', '>=', $startDate)
                                                    ->whereDate('reservation_date', '<=', $endDate)
                                            );
                                } else {
                                    $endDate = Carbon::parse($dateFilter[0])->endOfDay()->format('Y-m-d H:i:s');
                                    // 1 date condition
                                    $query->where(fn ($q) =>
                                                $q->whereNotNull('action_date')
                                                    ->whereDate('action_date', '>=', $startDate)
                                                    ->whereDate('action_date', '<=', $endDate)
                                            )
                                            ->orWhere(fn ($q) =>
                                                $q->whereNull('action_date')
                                                    ->whereDate('reservation_date', '>=', $startDate)
                                                    ->whereDate('reservation_date', '<=', $endDate)
                                            );
                                }
                            })
                            ->with([
                                'reservedFor.reservations', 
                                'reservedFor.reservationCancelled', 
                                'reservedFor.reservationAbandoned', 
                                'reservedBy', 
                                'handledBy'
                            ])
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

    public function getOccupiedTables()
    {
        // Get tables that are not 'Empty Seat'
        $occupiedTables = Table::where('status', '!=', 'Empty Seat')->get();
    
        // Get reserved tables
        $reservedTableIds = Reservation::whereNotIn('status', ['Cancelled', 'Completed', 'No show']) // Adjust status if needed
                                        ->pluck('table_no') // Get JSON arrays of table_no
                                        ->flatMap(function ($tableNos) {
                                            return array_map(function ($table) {
                                                return $table['id'];
                                            }, $tableNos); // Decode JSON only if it's a string
                                        })
                                        ->unique();

        // Get table records for reserved tables
        $reservedTables = Table::whereIn('id', $reservedTableIds)->get();
    
        // Merge both occupied and reserved tables
        $allOccupiedTables = $occupiedTables->merge($reservedTables)->unique('id');
    
        return response()->json($allOccupiedTables);
    }
}
