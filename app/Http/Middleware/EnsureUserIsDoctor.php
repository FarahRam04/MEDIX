<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDoctor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تحقق إن المستخدم Authenticated
        $user = $request->user();
        if ($user && $user->hasRole('doctor')) {
            return $next($request);
        }
        return response()->json('Unauthorized,only doctors');
    }
}
