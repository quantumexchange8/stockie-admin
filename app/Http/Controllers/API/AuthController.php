<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                Rule::exists('users')->where(function ($query) use ($request) {
                    $query->where('email', $request->email)
                            ->where('position', 'waiter');
                }),
            ],
            'password' => 'required|string',
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email.',
            'email.exists' => "We couldn't find this email. Please try again.",
            'password.required' => 'Please enter your password.',
        ]);
   
        if($validator->fails()){
            $response = [
                'status' => 'Error logging in',
                'errors' => $validator->errors()
            ];
      
            return response()->json($response, 401);    
        }

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $response = [
                'status' => 'Error logging in',
                'errors' => [ 'passwowrd' => [ 'Incorrect password. Please try again. Kindly reach out to your admin if you need further assistance.' ] ]
            ];
      
            return response()->json($response, 401);   
        }

        $user = User::where('position', 'waiter')->find(Auth::user()->id);

        // Revoke all existing tokens before creating a new one
        $user->tokens()->delete();

        $response = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'image' => $user->getFirstMediaUrl('user'),
            'token' => $user->createToken('mobile_app_token')->plainTextToken,
            'status' => 'Logged in',
        ];
  
        return response()->json($response, 200);
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        // Revoke the current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'Logged out successfully'
        ]);
    }
}
