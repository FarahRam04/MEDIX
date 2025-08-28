<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminOrReceptionistOrDoctors
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->hasRole('admin') || $user->hasRole('receptionist')||$user->hasRole('doctor'))) {
            return $next($request);
        }


        return response()->json([
            'message' => 'Forbidden: Access is restricted to Admins and Receptionists  and Doctors only.'
        ], 403);
    }
}
