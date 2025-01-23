<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Log;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::withoutRole('Super Admin')->with('permissions:name')->get();

        $users->each(function($user){
            $user->image = $user->getFirstMediaUrl('user');
        });

        return Inertia::render('AdminUser/AdminUser', [
            'user' => $users,
        ]);
    }

    public function deleteAdmin(String $id)
    {
        $targetUser = User::find($id);

        activity()->useLog('delete-sub-admin')
                    ->performedOn($targetUser)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'sub_admin' => $targetUser->full_name,
                    ])
                    ->log("Sub admin '$targetUser->full_name' is deleted.");

        $targetUser->delete();

        return redirect()->back();
    }

    public function editPermission(Request $request)
    {
        $user = User::find($request->input('id'));

        if($user->hasPermissionTo($request->input('permission')['value'])) {
            $user->revokePermissionTo($request->input('permission')['value']);
        } else {
            $user->givePermissionTo($request->input('permission')['value']);
        };

        // return redirect()->back();

        $data = $this->getAdminUsers();
        return response()->json($data);
    }

    public function editDetails(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required',
            'role_id' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'password' => ['required', Password::defaults()],
        ], [
            'required' => 'This field is required.',
            'string' => 'Invalid input.',
            'max' => 'Invalid input.'
        ]);

        $targetUser = User::where('id', $validatedData['id'])->first();
        $targetUser->update([
            'role_id' => $validatedData['role_id'],
            'full_name' => $validatedData['full_name'],
            'position' => $validatedData['position'],
            'password' => Hash::make($validatedData['password']),
        ]);

        activity()->useLog('edit-sub-admin')
                    ->performedOn($targetUser)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'sub_admin' => $targetUser->full_name,
                    ])
                    ->log("Sub-admin $targetUser->name's detail is updated.");

        if($request->hasfile('image')){
            $targetUser->clearMediaCollection('user');
            $targetUser->addMedia($request->image)->toMediaCollection('user');
        }

        // $data = $this->getAdminUsers();

        // return response()->json($data);
        // return redirect()->back();
    }

    public function addSubAdmin(Request $request)
    {
        $validatedData = $request->validate([
            'full_name' => ['required', 'max:255', 'string', 'unique:users,full_name'],
            'role_id' => ['required', 'max:255', 'string', 'unique:users,role_id'],
            'position' => ['required', 'max:255', 'string'],
            'password' => ['required'],
        ], [
            'required' => 'This field is required.',
            'max' => 'Invalid input.',
            'string' => 'Invalid format.',
            'unique' => 'This data already exists. Please try another one.'
        ]);

        $targetUser = User::create([
            'full_name' => $validatedData['full_name'],
            'name' => $validatedData['full_name'],
            'email' => $request->input('email') ? $request->input('email') : '',
            'password' => Hash::make($validatedData['password']),
            'role_id' => $validatedData['role_id'],
            'position' => $validatedData['position'],
        ]);

        activity()->useLog('create-sub-admin')
                    ->performedOn($targetUser)
                    ->event('added')
                    ->withProperties([
                        'created_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'sub_admin_name' => $targetUser->full_name,
                    ])
                    ->log("Sub-admin '$targetUser->full_name' is added.");

        if($request->hasFile('image')){
            $targetUser->addMedia($request->image)->toMediaCollection('user');
        }

        $targetUser->givePermissionTo($request->input('permission'));

        // $data = $this->getAdminUsers();

        // return response()->json($data);
        return redirect()->back();
    }

    private function getAdminUsers()
    {
        $users = User::withoutRole('Super Admin')->with('permissions:name')->get();

        $users->each(function($user){
            $user->image = $user->getFirstMediaUrl('user');
        });

        return $users;
    }

    public function refetchAdminUsers()
    {
        $users = $this->getAdminUsers();
        return response()->json($users);
    }
}
