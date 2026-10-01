<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }
        
        $user = Auth::user();

        // Every non-admin role uses the user portal. Access to individual
        // features is controlled by the permission middleware on each route.
        if (!$user->role || $user->role->RoleCode === 'admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Access denied. Non-admin privileges required.',
                    'role_mismatch' => true,
                    'current_role' => $user->role?->RoleCode ?? 'none',
                    'required_role' => 'non-admin'
                ], 403);
            }
            
            return response()->view('errors.403', [
                'role_mismatch' => true,
                'current_role' => $user->role?->RoleCode ?? 'none',
                'required_role' => 'non-admin',
                'user_name' => $user->FullName
            ], 403);
        }

        return $next($request);
    }
}
