<?php

namespace App\Http\Controllers;

use App\Http\Requests\TableRoomRequest;
use App\Models\Reservation;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Zone;
use App\Models\Table;
use Illuminate\Validation\Rule;

class TableRoomController extends Controller
{
    public function index(Request $request)
    {
        $zones = Zone::with(['tables' => fn ($query) => $query->where('state', 'active')])
                        ->where('status', 'active')
                        ->select('id', 'name')
                        ->get()
                        ->map(function ($zone) {
                            return [
                                'text' => $zone->name,
                                'value' => $zone->id,
                                'tables' => $zone->tables->map(function ($table) {
                                    return [
                                        'id'=> $table->id,
                                        'type'=> $table->type,
                                        'table_no'=> $table->table_no,
                                        'seat'=> $table->seat,
                                        'zone_id' => $table->zone_id,
                                    ];})->toArray()
                            ];
                        });
                        // dd($zones);

        $message = $request->session()->get('message');

        return Inertia::render('TableRoom/TableRoom', [
            'message' => $message ?? [],
            'zones' => $zones
        ]);
    }

    public function getZoneDetails() {
        $zones = Zone::with(['tables' => fn ($query) => $query->where('state', 'active')])
                        ->where('status', 'active')
                        ->select('id', 'name')
                        ->get()
                        ->map(function ($zone) {
                            return [
                                'text' => $zone->name,
                                'value' => $zone->id,
                                'tables' => $zone->tables->map(function ($table) {
                                    return [
                                        'id'=> $table->id,
                                        'type'=> $table->type,
                                        'table_no'=> $table->table_no,
                                        'seat'=> $table->seat,
                                        'zone_id' => $table->zone_id,
                                    ];})->toArray()
                            ];
                        });

        return response()->json($zones);
    }

    public function getTableDetails(){
        $tables = Table::select('id','type','table_no', 'seat', 'zone_id')
                        ->get();
    }

    public function addZone(Request $request)
    {
        // dd($request->all());
        $zonesError = [];
        $validatedZones = [];
        foreach ($request->input('zones') as $zones) {
            if(!isset($zones['index'])) {
                continue;
            }

            $zonesValidator = Validator::make($zones, [
                'index' => 'required|integer',
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('zones')->whereNull('deleted_at'),
                ]
            ], [
                'name.required' => 'Zone name is required.',
                'name.string' => 'Invalid input.',
                'name.unique' => 'Zone name already exists. Try another one.'
            ]);

            if ($zonesValidator->fails()) {
                foreach ($zonesValidator->errors()->messages() as $field => $messages) {
                    $zonesError["zones.{$zones['index']}.$field"] = $messages;
                }
            } else {
                $validated = $zonesValidator->validated();
                if(isset($validated['index'])){
                    $validated['index'] = $zones['index'];
                }
                $validatedZones[] = $validated;
            }
        }

        if(!empty($zonesError)){
            return redirect()->back()->withErrors($zonesError);
        }

        foreach($validatedZones as $newZones) {
            $newZone = Zone::create([
                'name' => $newZones['name']
            ]);

            activity()->useLog('create-zone')
                        ->performedOn($newZone)
                        ->event('added')
                        ->withProperties([
                            'created_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'zone_name' => $newZone->name,
                        ])
                        ->log("Zone '$newZone->name' is added.");
        }

        return redirect()->route('table-room');
    }

    public function addTable(Request $request)
    {
        $validatedData = $request->validate([
            // 'type'=>'required',
            'table_no'=>['required','string','max:255',
            Rule::unique('tables')->whereNull('deleted_at')],
            'seat'=> 'required|integer',
            'zone_id'=> 'required|integer',
        ]);

        $newTable = Table::create([
            'type'=> 'table',
            'table_no' => $validatedData['table_no'],
            'seat' => $validatedData['seat'],
            'zone_id' => $validatedData['zone_id'],
            'status' => 'Empty Seat',
        ]);

        activity()->useLog('create-table')
                    ->performedOn($newTable)
                    ->event('added')
                    ->withProperties([
                        'created_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'table_no' => $newTable->table_no,
                    ])
                    ->log("Table '$newTable->table_no' is added.");

        return redirect()->route('table-room');
    }

    public function deleteZone($id)
    {
        $zone = Zone::with('tables:id,table_no,zone_id,status,state')
                            ->where('id', $id)
                            ->first();
        
        $tables = $zone->tables;

        if ($tables->every(fn ($table) => $table->status === 'Empty Seat')) {
            if ($tables->count() > 0) {
                $tables->each(function ($table) {
                    $table->update(['state' => 'inactive']);

                    activity()->useLog('delete-table')
                                ->performedOn($table)
                                ->event('deleted')
                                ->withProperties([
                                    'edited_by' => auth()->user()->full_name,
                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                    'table_no' => $table->table_no,
                                ])
                                ->log("Table '$table->table_no' is deleted.");
                });
            }

            $zone->delete();
    
            activity()->useLog('delete-zone')
                        ->performedOn($zone)
                        ->event('deleted')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'zone_name' => $zone->name,
                        ])
                        ->log("Zone '$zone->name' is deleted.");
    
            return response()->json([
                'severity' => 'success',
                'summary' => "Selected zone has been deleted successfully."
            ]);
        }

        return response()->json([
            'severity' => 'warn',
            'summary' => "Selected zone still has some table that are checked in."
        ]);

        // $targetTable = Table::where('zone_id', $id)->get();

        // if ($targetTable->every(fn ($table) => $table->status === 'Empty Seat')) {
        //     if ($targetTable->count() > 0) {
        //         $targetTable->each(function ($table) {
        //             activity()->useLog('delete-table')
        //                         ->performedOn($table)
        //                         ->event('deleted')
        //                         ->withProperties([
        //                             'edited_by' => auth()->user()->full_name,
        //                             'image' => auth()->user()->getFirstMediaUrl('user'),
        //                             'table_no' => $table->table_no,
        //                         ])
        //                         ->log("Table '$table->table_no' is deleted.");
    
        //             $table->delete();
        //         });
        //     }
    
        //     $deleteZone = Zone::where('id', $id)->first();

        //     activity()->useLog('delete-zone')
        //                 ->performedOn($deleteZone)
        //                 ->event('deleted')
        //                 ->withProperties([
        //                     'edited_by' => auth()->user()->full_name,
        //                     'image' => auth()->user()->getFirstMediaUrl('user'),
        //                     'zone_name' => $deleteZone->name,
        //                 ])
        //                 ->log("Zone '$deleteZone->name' is deleted.");

        //     $deleteZone->delete();
    
        //     $message = [
        //         'severity' => 'success',
        //         'summary' => "Selected zone has been deleted successfully."
        //     ];
    
        //     return redirect()->route('table-room')->with(['message' => $message]);
        // }
            
        // $message = [
        //     'severity' => 'warn',
        //     'summary' => "Selected zone still has some table that are checked in."
        // ];

        // return redirect()->route('table-room')->with(['message' => $message]);
    }

    public function deleteTable(Request $request)
    {
        $table = Table::where('id', $request->id)->first();

        // Step 1: Check reservations that include this table ID in JSON
        $reservedStatuses = ['Pending', 'Delayed']; // Add more if needed

        $reservations = Reservation::where(function ($query) use ($reservedStatuses) {
                                        $query->whereIn('status', $reservedStatuses);
                                    })
                                    ->whereJsonContains('table_no', ['id' => $table->id])
                                    ->get();

        if ($reservations->count() > 0 && $request->params['confirmation'] == false) {
            // Step 2: Return confirmation response 
            return response()->json([
                'type' => 'reservation',
                'title' => 'Cancel reservations',
                'message' => 'There are active reservations made for this table. Are you sure you want to delete the selected table? This action cannot be undone.'
            ]);
        }

        // Step 3: Check table status
        if ($table->status !== 'Empty Seat') {
            return response()->json([
                'type' => 'order',
                'summary' => 'Unable to delete table',
                'detail' => "Table $table->table_no is currently checked in. To delete the table, it must be freed up first."
            ]);
        }

        $table->update(['state' => 'inactive']);
        
        activity()->useLog('delete-table')
                    ->performedOn($table)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'table_no' => $table->table_no,
                    ])
                    ->log("Table '$table->table_no' is deleted.");

        $message = [
            'severity' => 'success',
            'summary' => "Selected $table->type has been deleted successfully."
        ];

        return redirect()->route('table-room')->with(['message' => $message]);
    }

    public function editTable(Request $request)
    {
        // dd($request->toArray());
        $validatedData = $request->validate([
            'table_no'=> ['required','string','max:255',
            Rule::unique('tables', 'table_no')->ignore($request->id)->whereNull('deleted_at')],
            'seat' => 'required|integer|max:255',
            'zone_id' => 'required'
        ]);

        $editTable = Table::where('id', $request->id)->first();
        if ($editTable->table_no !== $validatedData['table_no']) {
            $request->validate([
                'table_no' => 'unique:tables,table_no'
            ]);
        }

        $editTable->update([
            'table_no' => $validatedData['table_no'],
            'seat' => $validatedData['seat'],
            'zone_id' => $validatedData['zone_id'],
        ]);

        activity()->useLog('edit-table-detail')
                    ->performedOn($editTable)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'table_no' => $editTable->table_no,
                    ])
                    ->log("Table '$editTable->table_no' is updated.");

        return redirect()->route('table-room');
    }

    public function editZone(Request $request)
    {
        // dd($request->all());
        $validatedData = $request->validate([
            'edit_name' => ['required','string','max:255',
            Rule::unique('zones','name')->ignore($request->id)->whereNull('deleted_at')],
        ]);

        $editZone = Zone::where('id', $request->id)->first();
        $editZone->update([
            'name' => $validatedData['edit_name'],
        ]);

        activity()->useLog('edit-zone-name')
                    ->performedOn($editZone)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'zone_name' => $editZone->name,
                    ])
                    ->log("Zone name '$editZone->name' is updated.");

    }
}
