<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Zone;

class TableRoomController extends Controller
{
    public function index()
    {

        $zones = Zone::select('id', 'name')->get();

        return Inertia::render('TableRoom/TableRoom', [
            'zones' => $zones
        ]);
    }

    public function addZone(Request $request)
    {
        $zone = Zone::create([
            'name'=> $request->name
        ]);

        return redirect()->route('tableroom.get-zones')->with('success','Zone created successfully');
    }
}
