<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Handle checking for existing auth user.
     */
    public function checkForAuthUser()
    {
        return response()->json(auth()->check());
    }

    /**
     * Handle an incoming authentication request.
     */
    public function authenticateCredentials(Request $request)
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
      
            return $response;    
        }

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $response = [
                'status' => 'Error logging in',
                'errors' => [ 'passwowrd' => [ 'Incorrect password. Please try again. Kindly reach out to your admin if you need further assistance.' ] ]
            ];
      
            return $response;   
        }

        $user = User::where('position', 'waiter')->find(Auth::user()->id);

        if (!$user) {
            $response = [
                'status' => 'Error logging in',
                'errors' => [ 'email' => [ 'Only waiters are able to log in here!' ] ]
            ];
      
            return $response;   
        }

        $response = [
            'status' => 'Success',
            'user' => $user
        ];

        return $response;
    }

    /**
     * Handle checking for user existing token.
     */
    public function checkForExistingToken(Request $request)
    {
        $response = $this->authenticateCredentials($request);

        if ($response['status'] === 'Error logging in') {
            return response()->json($response, 401);   
        }

        $authenticatedUser = $response['user'];

        $isWaiterLoggedIn = $authenticatedUser->tokens()
                                    ->where('name', 'mobile_app_token')
                                    ->whereNull('expires_at') // or ->where('expires_at', '>', now()) if you use expiry
                                    ->exists();

        $response = [
            'is_logged_in' => $isWaiterLoggedIn,
            'status_code' => $isWaiterLoggedIn ? 401 : 200
        ];
  
        return response()->json($response['is_logged_in'], $response['status_code']);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $response = $this->authenticateCredentials($request);

        if ($response['status'] === 'Error logging in') {
            return response()->json($response, 401);   
        }    

        $authenticatedUser = $response['user'];

        // Revoke all existing tokens before creating a new one
        $authenticatedUser->tokens()->delete();

        $response = [
            'id' => $authenticatedUser->id,
            'full_name' => $authenticatedUser->full_name,
            'email' => $authenticatedUser->email,
            'image' => $authenticatedUser->getFirstMediaUrl('user'),
            'token' => $authenticatedUser->createToken('mobile_app_token')->plainTextToken,
            'status' => 'Logged in',
        ];
  
        return response()->json($response, 200);
    }

    /**
     * Change waiter profile image.
     */
    public function updateProfilePicture(Request $request)
    {
        $waiter = User::find(Auth::user()->id);

        if ($request->hasFile('image')){
            $waiter->clearMediaCollection('user');
            $waiter->addMedia($request->image)->toMediaCollection('user');
        }

        return response()->json([
            'status' => 'success',
            'title' => 'You have sucessfully changed your profile picture.',
        ]);
    }

    /**
     * Handle logout request.
     */
    public function logout()
    {
        try {
            // Revoke the current token
            Auth::user()->currentAccessToken()->delete();

            return response()->json([
                'status' => 'success',
                'title' => 'You have logged out successfully',
            ], 201);

        } catch (\Exception  $e) {
            Log::error('Error logging user out: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'title' => 'Error logging user out.',
                'errors' => $e
            ], 422);
        };
    }

    /**
     * Handle logout request.
     */
    public function getAuthUser()
    {
        $waiter = Auth::user();
        $waiter->image = $waiter->getFirstMediaUrl('user');
        $waiter->waiter_position = $waiter->waiterPosition;

        return $waiter;
    }
}
