<?php

namespace App\Http\Controllers;

use App\Http\Requests\TableRoomRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Zone;
use App\Models\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TableRoomController extends Controller
{
    public function index()
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
        return Inertia::render('TableRoom/TableRoom', [
            'zones' => $zones
        ]);
    }

    public function getTableDetails(){
        $tables = Table::select('id','type','table_no', 'seat', 'zone_id')
                        ->get();
    }

    public function addZone(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required','string','max:255',
            Rule::unique('zones', 'name')->whereNull('deleted_at'),]
        ]);
        
        Zone::create([
            'name' => $validatedData['name'],
        ]);

        return redirect()->route('table-room')->with('success','Zone created successfully');
    }

    public function addTable(Request $request)
    {
        $validatedData = $request->validate([
            'type'=>'required',
            'table_no'=>['required','string','max:255',
            Rule::unique('tables')->whereNull('deleted_at')],
            'seat'=> 'required|integer',
            'zone_id'=> 'required|integer',
        ]);

        Table::create([
            'type'=> $validatedData['type'],
            'table_no' => $validatedData['table_no'],
            'seat' => $validatedData['seat'],
            'zone_id' => $validatedData['zone_id'],
            'status' => 'empty'
        ]);

        return redirect()->route('table-room')->with('success','Table created');
    }

    public function deleteZone($id)
    {
        $deleteZone = Zone::where('id', $id)->delete();
        $deleteTable = Table::where('zone_id', $id)->delete();

        return redirect()->route('table-room')->with('success','Zone deleted');
    }

    public function deleteTable(Request $request)
    {
        $deleteTable = Table::where('id', $request->id);
        $deleteTable->delete();

        // return redirect()->route('table-room')->with('success','Table deleted');
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

        return redirect()->route('tableroom.getTableDetails')->with('success','Table updated');
    }

    public function editZone(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required','string','max:255',
            Rule::unique('zones','name')->ignore($request->id)->whereNull('deleted_at')],
        ]);

        $editZone = Zone::find($request->id);
        $editZone->update([
            'name' => $validatedData['name'],
        ]);

        return redirect()->route('tableroom-getTableDetails')->with('success','Zone updated');
    }
}