<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Log;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = $this->getAdminUsers();

        return Inertia::render('AdminUser/AdminUser', [
            'user' => $users,
        ]);
    }

    public function deleteAdmin(string $id)
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

        $targetUser->update(['status' => 'Inactive']);

        $data = $this->getAdminUsers();

        return response()->json($data);
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
        $subAdminId = $request->id;

        $rules = [
            'id' => 'required',
            'full_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'image' => 'required|max:8000',
            'password' => [
                $subAdminId ? 'nullable' : 'required',
                Password::defaults(),
                'string'
            ],
            'email' => [
                'required',
                'email',
                $subAdminId 
                        ? Rule::unique('users')->ignore($subAdminId)->whereNull('deleted_at')
                        : 'unique:users,email,NULL,id,deleted_at,NULL'
            ],
            'role_id' => [
                'required',
                'string',
                'max:255',
                $subAdminId
                        ? Rule::unique('users')->ignore($subAdminId)->whereNull('deleted_at')
                        : 'unique:users,role_id,NULL,id,deleted_at,NULL'
            ]
        ];

        $messages = [
            'required' => 'This field is required.',
            'full_name.max' => 'Invalid input.',
            'role_id.max' => 'Invalid input.',
            'position.max' => 'Invalid input.',
            'image.max' => 'The size of the image is too big.',
            'string' => 'Invalid format.',
            'role_id.unique' => 'This id already exists. Please try another one.',
            'email.unique' => 'This email already exists. Please try another one.'
        ];

        // Validate the form data
        $validatedData = $request->validate($rules, $messages);

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
            $targetUser->addMedia($validatedData['image'])->toMediaCollection('user');
        }

        $data = $this->getAdminUsers();

        return response()->json($data);
        // return redirect()->back();
    }

    public function addSubAdmin(Request $request)
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'role_id' => 'required|string|max:255|unique:users,role_id',
            'position' => 'required|string|max:255',
            'password' => 'required',
            'image' => 'required|max:8000',
            'email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL'
        ];

        $messages = [
            'required' => 'This field is required.',
            'full_name.max' => 'Invalid input.',
            'role_id.max' => 'Invalid input.',
            'position.max' => 'Invalid input.',
            'image.max' => 'The size of the image is too big.',
            'string' => 'Invalid format.',
            'role_id.unique' => 'This id already exists. Please try another one.',
            'email.unique' => 'This email already exists. Please try another one.',
        ];

        // Validate the form data
        $validatedData = $request->validate($rules, $messages);

        $targetUser = User::create([
            'name' => $validatedData['full_name'],
            'full_name' => $validatedData['full_name'],
            'email' => $request->input('email') ? $request->input('email') : '',
            'password' => Hash::make($validatedData['password']),
            'role_id' => $validatedData['role_id'],
            'position' => $validatedData['position'],
            'employment_type'=> 'Full-time'
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
            $targetUser->addMedia($validatedData['image'])->toMediaCollection('user');
        }

        $targetUser->givePermissionTo($request->input('permission'));

        $data = $this->getAdminUsers();

        return response()->json($data);
        // return redirect()->back();
    }

    private function getAdminUsers()
    {
        $users = User::withoutRole('Super Admin')
                    ->with('permissions:name')
                    ->where([
                        ['position', '!=', 'waiter'],
                        ['status', 'Active']
                    ])
                    ->get();

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
