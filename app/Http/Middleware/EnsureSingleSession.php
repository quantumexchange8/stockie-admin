<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EnsureSingleSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $currentSessionId = session()->getId();
            $userId = Auth::user()->id;
            
            $activeSession = DB::table('sessions')
                ->where('user_id', $userId)
                ->where('id', $currentSessionId)
                ->first();
                
            if (!$activeSession) {
                Auth::logout();
                return redirect('/login')->withErrors(['msg' => 'This account is active on another device.']);
            }
        }
        
        return $next($request);
    }
}
