<?php

namespace App\Http\Controllers;
use App\Http\Requests\WaiterRequest;
use App\Models\Waiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class WaiterController extends Controller
{
   public function waiter(){
    return Inertia::render('Waiter/Waiter');
    }


   public function store(WaiterRequest $request)
   {
         
       $waiter = Waiter::create([
           'name' => $request->name,
           'phone' => '+60' . $request->phone,
           'email'=>$request->email,
           'staffid'=>$request->staffid,
           'salary'=>$request->salary,
           'stockie_email'=>$request->stockie_email,
           'stockie_password' => Hash::make($request->stockie_password),
       ]);
 
   }
}
