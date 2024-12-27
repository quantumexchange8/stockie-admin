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
            Zone::create([
                'name' => $newZones['name']
            ]);
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

        Table::create([
            'type'=> 'table',
            'table_no' => $validatedData['table_no'],
            'seat' => $validatedData['seat'],
            'zone_id' => $validatedData['zone_id'],
            'status' => 'Empty Seat',
        ]);

        return redirect()->route('table-room');
    }

    public function deleteZone($id)
    {
        Zone::where('id', $id)->delete();
        Table::where('zone_id', $id)->delete();

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

        $editTable = Table::find($request->id);
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

        return redirect()->route('table-room');
    }

    public function editZone(Request $request)
    {
        // dd($request->all());
        $validatedData = $request->validate([
            'edit_name' => ['required','string','max:255',
            Rule::unique('zones','name')->ignore($request->id)->whereNull('deleted_at')],
        ]);

        $editZone = Zone::find($request->id);
        $editZone->update([
            'name' => $validatedData['edit_name'],
        ]);

    }
}
