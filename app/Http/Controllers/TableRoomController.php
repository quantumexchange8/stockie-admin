<?php

namespace App\Http\Controllers;

use App\Http\Requests\TableRoomRequest;
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
        $zones = Zone::with('tables')
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
        $zones = Zone::with('tables')
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
        $deleteZone = Zone::where('id', $id)->first();
        activity()->useLog('delete-zone')
                    ->performedOn($deleteZone)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'zone_name' => $deleteZone->name,
                    ])
                    ->log("Zone '$deleteZone->name' is deleted.");
        $deleteZone->delete();

        $deleteTable = Table::where('zone_id', $id);
        activity()->useLog('delete-table')
                    ->performedOn($deleteTable)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'table_no' => $deleteTable->table_no,
                    ])
                    ->log("Table '$deleteTable->table_no' is deleted.");
        $deleteTable->delete();

        $message = [
            'severity' => 'success',
            'summary' => "Selected zone has been deleted successfully."
        ];

        return redirect()->route('table-room')->with(['message' => $message]);
    }

    public function deleteTable(Request $request)
    {
        
        $deleteType = Table::where('id', $request->id)->value('type');
        $deleteTable = Table::where('id', $request->id);
        $deleteTable->delete();

        $message = [
            'severity' => 'success',
            'summary' => "Selected $deleteType has been deleted successfully."
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
