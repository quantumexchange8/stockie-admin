<?php

namespace App\Http\Controllers;
use App\Http\Requests\WaiterRequest;
use App\Models\Order;
use App\Models\User;
use App\Models\Waiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class WaiterController extends Controller
{
   public function waiter(Request $request)
   {
        $waiters = Waiter::all();

        $message = $request->session()->get('message');

        $request->session()->forget('message');
        $request->session()->save();

        return Inertia::render('Waiter/Waiter', [
            'waiters'=> $waiters,
            'message'=> $message ?? [],
        ]);
    }


   public function store(WaiterRequest $request)
   {
    // dd($request->validated());
       $user = Waiter::create([
           'name' => $request->name,
           'phone' => '+60' . $request->phone,
           'email'=>$request->email,
           'staffid' => $request->staffid,
           'salary'=>$request->salary,
           'stockie_email'=>$request->stockie_email,
           'stockie_password' => Hash::make($request->stockie_password),
       ]);

       $message = [ 
        'severity' => 'success', 
        'summary' => 'New waiter has been successfully added.'
        ];

       return redirect()->route('waiter')->with(['message' => $message]);
   }

   public function deleteWaiter ($id)
   {
        $deleteWaiter = Waiter::where('id', $id);
        $deleteWaiter->delete();

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Selected waiter has been successfully deleted.'
        ];

        return Redirect::route('waiter')->with(['message' => $message]);
   }

   public function editWaiter (Request $request)
   {
        $validateData = $request->validate([
            'name'=> 'required|string|max:255',
            'phone'=> 'required|string|regex:/^\+?[0-9]{7,15}$/',
            'email'=> 'required|email|unique:users,email',
            'staffid'=>'required|string',
            'salary'=> 'required|integer|min:0',
            'stockie_email'=>'required|email',
            'stockie_password' => 'required|string',
        ]);

        $editWaiter = Waiter::find($request->id);
        $editWaiter->update([
            'name'=> $validateData['name'],
            'phone'=> '+60'.$validateData['phone'],
            'email'=> $validateData['email'],
            'staffid'=> $validateData['staffid'],
            'salary'=> $validateData['salary'],
            'stockie_email'=> $validateData['stockie_email'],
            'stockie_password'=> Hash::make($validateData['stockie_password']),
        ]);

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Changes saved'
        ];
        // dd($request->all());
        return redirect()->route('waiter')->with(['message' => $message]);
   }

   public function showWaiterDetails(string $id)
   {
        $dateFilter = [
            now()->subDays(7)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
            now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
        ];

        $order = Order::where('waiter_id', $id)
                ->whereBetween('created_at', $dateFilter)
                ->orderBy('created_at','desc')
                ->get();


        return Inertia::render('Waiter/Partials/WaiterDetails', [
            'id' => $id,
            'defaultDateFilter' => $dateFilter,
            'order' => $order,
        ]);
   }

   public function getWaiterDetailsWithId(string $id)
   {
        $waiterDetail = Waiter::where('id', $id)->find($id);
        return response()->json($waiterDetail);
   }

   public function salesHistory(string $id)
   {
        $dateFilter = [
            now()->subDays(7)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
            now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
        ];

        $order = Order::where('waiter_id', $id)
                ->whereBetween('created_at', $dateFilter)
                ->orderBy('created_at','desc')
                ->get();

        return response()->json($order);
   }
}
