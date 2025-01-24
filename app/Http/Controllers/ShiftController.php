<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index(){
        return Inertia::render('ShiftManagement/ShiftManagement', [

        ]);
    }

    public function record() {
        return Inertia::render('ShiftManagement/Partials/ShiftReport', [

        ]);
    }
}
