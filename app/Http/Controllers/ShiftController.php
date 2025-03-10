<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function viewShiftControl(){
        return Inertia::render('ShiftManagement/ShiftControl', [

        ]);
    }

    public function viewShiftRecord() {
        return Inertia::render('ShiftManagement/ShiftReport', [

        ]);
    }
}
