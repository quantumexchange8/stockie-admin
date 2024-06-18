<?php

namespace App\Http\Controllers;
use App\Http\Requests\WaiterRequest;
use App\Models\User;
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
         
       $user = User::create([
           'full_name'=>$request->full_name,
           'name' => $request->name,
           'phone' => '+60' . $request->phone,
           'email'=>$request->email,
           'role'=> 'staff',
           'role_id'=>$request->role_id,
           'salary'=>$request->salary,
           'worker_email'=>$request->worker_email,
           'password' => Hash::make($request->password),
       ]);
 
   }
}
