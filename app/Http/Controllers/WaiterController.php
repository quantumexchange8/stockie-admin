<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class WaiterController extends Controller
{
   public function waiter(){
    return Inertia::render('Waiter/Waiter');
   }
}
