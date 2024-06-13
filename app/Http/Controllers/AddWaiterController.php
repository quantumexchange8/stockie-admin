<?php

namespace App\Http\Controllers;

use App\Http\Requests\WaiterRequest;
use App\Models\Waiter;
use Illuminate\Support\Facades\Hash;

class AddWaiterController extends Controller
{
   
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
        return redirect()->back()->with('success', 'Waiter added successfully');

    }
}
