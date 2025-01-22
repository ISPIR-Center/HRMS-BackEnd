<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EmployeeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next)
    // {
    //     if (Auth::check() && Auth::user()->role === 'employee') {
    //         return $next($request); 
    //     }

      
    //     return response()->json(['message' => 'Unauthorized. Employees only.'], 403);
    // }

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user->role !== 'employee') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
